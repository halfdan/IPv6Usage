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

/**
 *
 * @package Piwik_IPv6Usage
 */
class Controller extends \Piwik\Plugin\Controller
{

    public function index()
    {
        $view = new \Piwik\View('@IPv6Usage/index');
        $this->setPeriodVariablesView($view);
        $view->graphIPv6UsageByProtocol = $this->getIPv6UsageEvolutionGraph(true, array('IPv6Usage_IPv4', 'IPv6Usage_IPv6', 'IPv6Usage_Teredo', 'IPv6Usage_Tun6to4'));
        echo $view->render();
    }

    public function getIPv6UsageEvolutionGraph($fetch = false, $columns = false)
    {
        if (empty($columns)) {
            $columns = \Piwik\Common::getRequestVar('columns');
            $columns = \Piwik\Piwik::getArrayFromApiParameter($columns);
        }

        $documentation = \Piwik\Piwik::translate('IPv6Usage_ProtocolUsageEvolution');

        // Note: if you edit this array, maybe edit the code below as well
        $selectableColumns = array(
            'IPv6Usage_IPv4',
            'IPv6Usage_IPv6',
            'IPv6Usage_Teredo',
            'IPv6Usage_Tun6to4',
            'nb_visits',
            'nb_uniq_visitors'
        );

        $view = $this->getLastUnitGraphAcrossPlugins($this->pluginName, __FUNCTION__, $columns,
            $selectableColumns, $documentation);
        return $this->renderView($view, $fetch);
    }

    public function getIPv6UsageGraph($fetch = false)
    {
        $view = \Piwik\ViewDataTable\Factory::build('graphPie', 'IPv6Usage.getVisitsByProtocol', 'IPv6Usage.getIPv6UsageGraph');

        $view->translations['label'] = \Piwik\Piwik::translate('IPv6Usage_IPProtocol');
        $view->filter_sort_column = 'label';
        $view->filter_sort_order = 'asc';
        $view->filter_limit = 2;

        $view->show_search = false;
        $view->show_exclude_low_population = false;
        $view->show_offset_information = false;

        return $this->renderView($view, $fetch);
    }
}
