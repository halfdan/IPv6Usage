<?php
/**
 * Piwik - Open source web analytics
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 */

/**
 * @package Piwik_IPv6Usage
 */
class Piwik_IPv6Usage_API
{
	static private $instance = null;

	/**
	 * @return Piwik_IPv6Usage_API
	 */
	static public function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}


	/**

	 */
	public function getVisitEvolution( $idSite, $period, $date )
	{
                Piwik::checkUserHasViewAccess( $idSite );
		$dataTable = $this->getNumeric( $idSite, $period, $date, 'IPv6Usage_IPv6');
		return $dataTable;
	}

	public function getVisitsByProtocol( $idSite, $period, $date ) {
                Piwik::checkUserHasViewAccess( $idSite );
                $archive = Piwik_Archive::build($idSite, $period, $date );
                $ipv4 = $archive->getNumeric('IPv6Usage_IPv4');

		$dataTable = new Piwik_DataTable();

		if($ipv4) {
			$newRow = new Piwik_DataTable_Row();
			$newRow->setColumns(array(
				'label' => 'IPv4',
				'nb_visits' => $ipv4
			));
			$dataTable->addRow($newRow);
		}

                $ipv6 = $archive->getNumeric('IPv6Usage_IPv6');

		if($ipv6) {
			$newRow = new Piwik_DataTable_Row();
			$newRow->setColumns(array(
				'label' => 'IPv6',
				'nb_visits' => $ipv6
			));
			$dataTable->addRow($newRow);
		}
                return $dataTable;
	}

	public function get( $idSite, $period, $date, $segment = false, $columns = false)
        {
                Piwik::checkUserHasViewAccess( $idSite );
                $archive = Piwik_Archive::build($idSite, $period, $date, $segment );

                // array values are comma separated
                $columns = Piwik::getArrayFromApiParameter($columns);

		if(empty($columns)) {
			$columns = array(
				'IPv6Usage_IPv4',
				'IPv6Usage_IPv6'
			);
		}

		// We need to fetch uniq visits for processing
		$columns[] = 'nb_visits';

		$dataTable = $archive->getDataTableFromNumeric($columns);

		$dataTable->filter('ColumnCallbackAddColumnPercentage', array('IPv6Usage_IPv6_rate', 'IPv6Usage_IPv6', 'nb_visits', 2));
		$dataTable->filter('ColumnCallbackAddColumnPercentage', array('IPv6Usage_IPv4_rate', 'IPv6Usage_IPv4', 'nb_visits', 2));

		// Delete column
		$dataTable->deleteColumn('nb_visits');
		return $dataTable;
	}

	protected function getNumeric( $idSite, $period, $date, $toFetch )
        {
                Piwik::checkUserHasViewAccess( $idSite );
                $archive = Piwik_Archive::build($idSite, $period, $date );
                $dataTable = $archive->getNumeric($toFetch);
                return $dataTable;
        }
}