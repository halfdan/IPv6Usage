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
        $query = $this->getLogAggregator()->queryVisitsByDimension(array('location_ip_protocol'));
        if($query == false) {
            return;
        }

        $data = array(
            'IPv6Usage_IPv4' => 0,
            'IPv6Usage_IPv6' => 0,
            'IPv6Usage_Teredo' => 0,
            'IPv6Usage_Tun6to4' => 0
        );

        while ($row = $query->fetch()) {
            if ($row['location_ip_protocol'] == 4) {
                $data['IPv6Usage_IPv4'] = $row[Metrics::INDEX_NB_VISITS];
            } elseif ($row['location_ip_protocol'] == 6) {
                $data['IPv6Usage_IPv6'] = $row[Metrics::INDEX_NB_VISITS];
            } elseif ($row['location_ip_protocol'] == 101) {
                $data['IPv6Usage_Teredo'] = $row[Metrics::INDEX_NB_VISITS];
            } elseif ($row['location_ip_protocol'] == 102) {
                $data['IPv6Usage_Tun6to4'] = $row[Metrics::INDEX_NB_VISITS];
            }
        }

        foreach ($data as $key => $value) {
            $this->getProcessor()->insertNumericRecord($key, $value);
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

