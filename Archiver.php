<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package IPv6Usage
 */

namespace Piwik\Plugins\IPv6Usage;

use Piwik\Common;
use Piwik\DataAccess\LogAggregator;
use Piwik\DataArray;
use Piwik\DataTable;
use Piwik\Metrics;

/**
 * Archiver for IPv6Usage Plugin
 *
 * @see PluginsArchiver
 */
class Archiver extends \Piwik\Plugin\Archiver
{
    public function aggregateDayReport()
    {
        $archiveProcessor = $this->getProcessor();
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

    public function aggregateMultipleReports()
    {
        $numericToSum = array(
            'IPv6Usage_IPv4',
            'IPv6Usage_IPv6',
            'IPv6Usage_Teredo',
            'IPv6Usage_Tun6to4'
        );
        $this->getProcessor()->aggregateNumericMetrics($numericToSum, 'sum');
    }
}

