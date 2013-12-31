<?php
/**
 * Piwik - Open source web analytics
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 */
namespace Piwik\Plugins\IPv6Usage;

/**
 * The IPv6Usage API lets you access the IPv6 access statistics of your site.
 *
 */
class API extends \Piwik\Plugin\API
{
    /**

     */
    public function getVisitEvolution($idSite, $period, $date)
    {
        \Piwik\Piwik::checkUserHasViewAccess($idSite);
        $dataTable = $this->getNumeric($idSite, $period, $date, 'IPv6Usage_IPv6');
        return $dataTable;
    }

    public function getVisitsByProtocol($idSite, $period, $date)
    {
        \Piwik\Piwik::checkUserHasViewAccess($idSite);
        $archive = \Piwik\Archive::build($idSite, $period, $date);
        $ipv4 = $archive->getNumeric('IPv6Usage_IPv4');

        $dataTable = new \Piwik\DataTable();

        if ($ipv4) {
            $newRow = new \Piwik\DataTable\Row();
            $newRow->setColumns(array(
                'label' => 'IPv4',
                'nb_visits' => $ipv4
            ));
            $dataTable->addRow($newRow);
        }

        $ipv6 = $archive->getNumeric('IPv6Usage_IPv6');

        if ($ipv6) {
            $newRow = new \Piwik\DataTable\Row();
            $newRow->setColumns(array(
                'label' => 'IPv6',
                'nb_visits' => $ipv6
            ));
            $dataTable->addRow($newRow);
        }
        $teredo = $archive->getNumeric('IPv6Usage_Teredo');

        if ($teredo) {
            $newRow = new \Piwik\DataTable\Row();
            $newRow->setColumns(array(
                'label' => 'Teredo',
                'nb_visits' => $teredo
            ));
            $dataTable->addRow($newRow);
        }
        $tun6to4 = $archive->getNumeric('IPv6Usage_Tun6to4');

        if ($tun6to4) {
            $newRow = new \Piwik\DataTable\Row();
            $newRow->setColumns(array(
                'label' => 'Tun6to4',
                'nb_visits' => $tun6to4
            ));
            $dataTable->addRow($newRow);
        }

        return $dataTable;
    }

    public function get($idSite, $period, $date, $segment = false, $columns = false)
    {
        \Piwik\Piwik::checkUserHasViewAccess($idSite);
        $archive = \Piwik\Archive::build($idSite, $period, $date, $segment);

        // array values are comma separated
        $columns = \Piwik\Piwik::getArrayFromApiParameter($columns);

        if (empty($columns)) {
            $columns = array(
                'IPv6Usage_IPv4',
                'IPv6Usage_IPv6',
                'IPv6Usage_Teredo',
                'IPv6Usage_Tun6to4'
            );
        }

        // We need to fetch uniq visits for processing
        $columns[] = 'nb_visits';

        $dataTable = $archive->getDataTableFromNumeric($columns);

        $dataTable->filter('ColumnCallbackAddColumnPercentage', array('IPv6Usage_IPv4_rate', 'IPv6Usage_IPv4', 'nb_visits', 2));
        $dataTable->filter('ColumnCallbackAddColumnPercentage', array('IPv6Usage_IPv6_rate', 'IPv6Usage_IPv6', 'nb_visits', 2));
        $dataTable->filter('ColumnCallbackAddColumnPercentage', array('IPv6Usage_Teredo_rate', 'IPv6Usage_Teredo', 'nb_visits', 2));
        $dataTable->filter('ColumnCallbackAddColumnPercentage', array('IPv6Usage_Tun6to4_rate', 'IPv6Usage_Tun6to4', 'nb_visits', 2));

        // Delete column
        $dataTable->deleteColumn('nb_visits');
        return $dataTable;
    }

    protected function getNumeric($idSite, $period, $date, $toFetch)
    {
        \Piwik\Piwik::checkUserHasViewAccess($idSite);
        $archive = \Piwik\Archive::build($idSite, $period, $date);
        $dataTable = $archive->getNumeric($toFetch);
        return $dataTable;
    }
}
