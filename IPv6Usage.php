<?php
/**
 * Piwik - Open source web analytics
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package Piwik_IPv6Usage
 */
namespace Piwik\Plugins\IPv6Usage;

use Piwik\Piwik;
use Piwik\Menu\MenuMain;
use Piwik\WidgetsList;

/**
 *
 * @package Piwik_IPv6Usage
 */
class IPv6Usage extends \Piwik\Plugin
{
    public function getListHooksRegistered()
    {
        return array(
            'Tracker.newVisitorInformation' => 'logIPv6Info',
            'ArchiveProcessor.Day.compute' => 'archiveDay',
            'ArchiveProcessor.Period.compute' => 'archivePeriod',
            'WidgetsList.addWidgets' => 'addWidgets',
            'API.getReportMetadata' => 'getReportMetadata',
            'Menu.Reporting.addItems' => 'addMenu'
        );
    }

    public function install()
    {
        // add column location_ip_protocol in the visit table
        $query = "ALTER IGNORE TABLE `" . \Piwik\Common::prefixTable('log_visit') . "` " .
            "ADD `location_ip_protocol` TINYINT( 1 ) NULL ";

        // if the column already exist do not throw error. Could be installed twice...
        try {
            \Piwik\Db::exec($query);
        } catch (\Exception $e) {
        }
    }

    /**
     * Add report metadata.
     * @param array $reports
     */
    public function getReportMetadata(&$reports)
    {
        $reports[] = array(
            'category' => Piwik::translate('General_Visitors'),
            'name' => Piwik::translate('IPv6Usage_ProtocolUsageEvolution'),
            'module' => 'IPv6Usage',
            'action' => 'get',
            'metrics' => array(
                'IPv6Usage_IPv4' => Piwik::translate('IPv6Usage_IPv4'),
                'IPv6Usage_IPv6' => Piwik::translate('IPv6Usage_IPv6'),
                'IPv6Usage_Teredo' => Piwik::translate('IPv6Usage_Teredo'),
                'IPv6Usage_Tun6to4' => Piwik::translate('IPv6Usage_Tun6to4'),
                'IPv6Usage_IPv4_rate' => Piwik::translate('IPv6Usage_IPv4_rate'),
                'IPv6Usage_IPv6_rate' => Piwik::translate('IPv6Usage_IPv6_rate'),
                'IPv6Usage_Teredo_rate' => Piwik::translate('IPv6Usage_Teredo_rate'),
                'IPv6Usage_Tun6to4_rate' => Piwik::translate('IPv6Usage_Tun6to4_rate')
            ),
            'processedMetrics' => false,
            'order' => 40
        );
    }

    public function uninstall()
    {
        $query = "ALTER TABLE `" . \Piwik\Common::prefixTable('log_visit') . "` DROP `location_ip_protocol`";
        \Piwik\Db::exec($query);
    }

    public function addWidgets()
    {
        WidgetsList::add('General_Visitors', 'IPv6Usage_ProtocolUsageEvolution', 'IPv6Usage', 'getIPv6UsageEvolutionGraph', array('columns' => array('IPv6Usage_IPv6')));
        WidgetsList::add('General_Visitors', 'IPv6Usage_WidgetProtocolDescription', 'IPv6Usage', 'getIPv6UsageGraph');
    }

    public function addMenu()
    {
        MenuMain::getInstance()->add('General_Visitors', 'IPv6 Usage', array('module' => 'IPv6Usage', 'action' => 'index'));
    }

    /**
     * @param array $visitorInfo
     */
    public function logIPv6Info(array &$visitorInfo)
    {
        // Fetch the users ip
        $ip = $visitorInfo['location_ip'];
        // Check the type of the IP (v4 or v6)
        $protocol = \Piwik\IP::isIPv4($ip) ? 4 : 6;
        #check if IPv6 is tunneled
        if ($protocol == 6) {
            #::ffff:0:0/96	ipv4mapped
            #$regex_ipv4map = '/(.f){4}(.0){20}.ip6.arpa$/i'; already detected by Piwik_IP::isIPv4

            #2001::/32	teredo
            $regex_teredo = '/([0-9a-f].){24}0.0.0.0.1.0.0.2.ip6.arpa$/i';

            #2002::/16	6to4
            $regex_6to4 = '/([0-9a-f].){28}2.0.0.2.ip6.arpa$/i';

            $rev_nibbles = $this->reverseIPv6Nibbles($ip);
            if (preg_match($regex_teredo, $rev_nibbles)) {
                $protocol = 101;
            } elseif (preg_match($regex_6to4, $rev_nibbles)) {
                $protocol = 102;
            }
        }
        $visitorInfo['location_ip_protocol'] = $protocol;
    }

    private function reverseIPv6Nibbles($addr)
    {
        #reverse nibbles by Alnitak on http://stackoverflow.com/questions/6619682/convert-ipv6-to-nibble-format-for-ptr-records
        $unpack = unpack('H*hex', $addr);
        $hex = $unpack['hex'];
        $arpa = implode('.', array_reverse(str_split($hex))) . '.ip6.arpa';
        return $arpa;
    }

    private function isIPv6($ip = false)
    {
        $ip = (!$ip) ? getenv("REMOTE_ADDR") : $ip;
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * @param \Piwik\ArchiveProcessor\Period $archiveProcessor  notification object
     * @return mixed
     */
    public function archivePeriod(\Piwik\ArchiveProcessor\Period $archiveProcessor)
    {
        if (!$archiveProcessor->shouldProcessReportsForPlugin($this->getPluginName())) return;

        $numericToSum = array(
            'IPv6Usage_IPv4',
            'IPv6Usage_IPv6',
            'IPv6Usage_Teredo',
            'IPv6Usage_Tun6to4'
        );
        $archiveProcessor->aggregateNumericMetrics($numericToSum, 'sum');
    }

    /**
     * @param \Piwik\ArchiveProcessor\Day $archiveProcessor Archive Processor
     * @return mixed
     */
    public function archiveDay(\Piwik\ArchiveProcessor\Day $archiveProcessor)
    {
        if (!$archiveProcessor->shouldProcessReportsForPlugin($this->getPluginName())) return;

        $select = "location_ip_protocol, COUNT( location_ip_protocol ) as count";
        $from = "log_visit";

        $where = "log_visit.visit_last_action_time >= ?
				AND log_visit.visit_last_action_time <= ?
				AND log_visit.idsite = ?
				AND location_ip_protocol IS NOT NULL
				GROUP BY location_ip_protocol";

        $bind = array($archiveProcessor->getDateStart(),
            $archiveProcessor->getDateEnd(), $archiveProcessor->getSite()->getId());

        $query = $archiveProcessor->getSegment()->getSelectQuery($select, $from, $where, $bind);
        $rowSet = \Piwik\Db::query($query['sql'], $query['bind']);

        $data = array(
            'IPv6Usage_IPv4' => 0,
            'IPv6Usage_IPv6' => 0,
            'IPv6Usage_Teredo' => 0,
            'IPv6Usage_Tun6to4' => 0
        );

        while ($row = $rowSet->fetch()) {
            if ($row['location_ip_protocol'] === "4") {
                $data['IPv6Usage_IPv4'] = $row['count'];
            } elseif ($row['location_ip_protocol'] === "6") {
                $data['IPv6Usage_IPv6'] = $row['count'];
            } elseif ($row['location_ip_protocol'] === "101") {
                $data['IPv6Usage_Teredo'] = $row['count'];
            } elseif ($row['location_ip_protocol'] === "102") {
                $data['IPv6Usage_Tun6to4'] = $row['count'];
            }
        }

        foreach ($data as $key => $value) {
            $archiveProcessor->insertNumericRecord($key, $value);
        }
    }
}
