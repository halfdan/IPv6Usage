<?php
/**
 * Piwik - Open source web analytics
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package Piwik_IPv6Usage
 */

/**
 *
 * @package Piwik_IPv6Usage
 */
class Piwik_IPv6Usage_Controller extends Piwik_Controller
{

	public function index() {
		$view = Piwik_View::factory('index');
		$this->setPeriodVariablesView($view);
		$view->graphIPv6UsageByProtocol = $this->getIPv6UsageEvolutionGraph( true, array('IPv6Usage_IPv4', 'IPv6Usage_IPv6', 'IPv6Usage_Teredo', 'IPv6Usage_Tun6to4') );
		echo $view->render();
	}

	public function getIPv6UsageEvolutionGraph( $fetch = false, $columns = false)
        {
                if(empty($columns))
                {
                        $columns = Piwik_Common::getRequestVar('columns');
                        $columns = Piwik::getArrayFromApiParameter($columns);
                }

                $documentation = Piwik_Translate('IPv6Usage_ProtocolUsageEvolution');

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

	public function getIPv6UsageGraph( $fetch = false)
	{
		$view = Piwik_ViewDataTable::factory('graphPie');
                $view->init($this->pluginName, __FUNCTION__, 'IPv6Usage.getVisitsByProtocol');

                $view->setColumnTranslation('label', Piwik_Translate('IPv6Usage_IPProtocol'));
                $view->setSortedColumn( 'label', 'asc' );

                $view->setLimit(2);
                $view->setGraphLimit(2);
                $view->disableSearchBox();
                $view->disableExcludeLowPopulation();
                $view->disableOffsetInformationAndPaginationControls();
                $this->setMetricsVariablesView($view);
		return $this->renderView($view, $fetch);
	}
}
