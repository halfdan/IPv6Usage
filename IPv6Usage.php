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
class Piwik_IPv6Usage extends Piwik_Plugin
{
	/**
	 * Return information about this plugin.
	 *
	 * @see Piwik_Plugin
	 *
	 * @return array
	 */
	public function getInformation()
	{
		return array(
			'description' => Piwik_Translate('IPv6Usage_PluginDescription'),
			'homepage' => 'http://github.com/halfdan/IPv6Usage',
			'author' => 'Fabian Becker <halfdan@xnorfz.de>',
			'author_homepage' => 'http://geekproject.eu/',
			'license' => 'GPL v3 or later',
			'license_homepage' => 'http://www.gnu.org/licenses/gpl.html',
			'version' => '0.1',
			'translationAvailable' => true,
			'TrackerPlugin' => true,
		);
	}

	public function getListHooksRegistered()
	{
		return array(
			'Tracker.newVisitorInformation' => 'logIPv6Info',
			'ArchiveProcessing_Day.compute' => 'archiveDay',
			'ArchiveProcessing_Period.compute' => 'archivePeriod',
			'WidgetsList.add' => 'addWidgets',
			'API.getReportMetadata' => 'getReportMetadata',
			'Menu.add' => 'addMenu'
		);
	}

        public function install()
        {
                // add column location_ip_protocol in the visit table
                $query = "ALTER IGNORE TABLE `".Piwik_Common::prefixTable('log_visit')."` " .
                                "ADD `location_ip_protocol` TINYINT( 1 ) NULL ";

                // if the column already exist do not throw error. Could be installed twice...
                try {
                        Piwik_Exec($query);
                }
                catch(Exception $e){
                }
        }

	/**
	 * @param Piwik_Event_Notification $notification  notification object
	 */
	public function getReportMetadata($notification)
	{
		$reports = &$notification->getNotificationObject();
		$reports[] = array(
			'category' => Piwik_Translate('General_Visitors'),
			'name' => Piwik_Translate('IPv6Usage_ProtocolUsageEvolution'),
			'module' => 'IPv6Usage',
			'action' => 'get',
			'metrics' => array(
				'IPv6Usage_IPv4' => Piwik_Translate('IPv6Usage_IPv4'),
				'IPv6Usage_IPv6' => Piwik_Translate('IPv6Usage_IPv6'),
				'IPv6Usage_IPv4_rate' => Piwik_Translate('IPv6Usage_IPv4_rate'),
				'IPv6Usage_IPv6_rate' => Piwik_Translate('IPv6Usage_IPv6_rate')
			),
			'processedMetrics' => false,
			'order' => 40
		);
	}

        function uninstall()
        {
                $query = "ALTER TABLE `".Piwik_Common::prefixTable('log_visit')."` DROP `location_ip_protocol`";
                Piwik_Exec($query);
        }

	function addWidgets()
        {
                Piwik_AddWidget( 'General_Visitors', 'IPv6Usage_ProtocolUsageEvolution', 'IPv6Usage', 'getIPv6UsageEvolutionGraph', array('columns' => array('IPv6Usage_IPv6')));
                Piwik_AddWidget( 'General_Visitors', 'IPv6Usage_WidgetProtocolDescription', 'IPv6Usage', 'getIPv6UsageGraph');
	}

	function addMenu()
        {
		Piwik_AddMenu('General_Visitors', 'IPv6 Usage', array('module' => 'IPv6Usage', 'action' => 'index'));
        }

	public function logIPv6Info($notification) {
		// retrieve the array of the visitors data from the notification object
		$visitorInfo =& $notification->getNotificationObject();
		// Fetch the users ip
		$ip = $visitorInfo['location_ip'];
		// Check the type of the IP (v4 or v6)
		$protocol = Piwik_IP::isIPv4($ip) ? 4 : 6;
		$visitorInfo['location_ip_protocol'] = $protocol;
	}

	function isIPv6($ip = false) {
		$ip = (!$ip) ? getenv("REMOTE_ADDR") : $ip;
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}

	function activate()
	{
		// Executed every time plugin is Enabled
	}

	function deactivate()
	{
		// Executed every time plugin is disabled
	}

	/**
	 * @param Piwik_Event_Notification $notification  notification object
	 * @return mixed
	*/
	function archivePeriod( $notification )
	{
		$archiveProcessing = $notification->getNotificationObject();

		if(!$archiveProcessing->shouldProcessReportsForPlugin($this->getPluginName())) return;

		$numericToSum = array(
				'IPv6Usage_IPv4',
				'IPv6Usage_IPv6'
		);
		$archiveProcessing->archiveNumericValuesSum($numericToSum);
	}

	/**
	 * @param Piwik_Event_Notification $notification  notification object
	 * @return mixed
	*/
	function archiveDay($notification)
	{
		/* @var $archiveProcessing Piwik_ArchiveProcessing */
		$archiveProcessing = $notification->getNotificationObject();

		if(!$archiveProcessing->shouldProcessReportsForPlugin($this->getPluginName())) return;

		$select = "location_ip_protocol, COUNT( location_ip_protocol ) as count";
 		$from = "log_visit";

		$where = "log_visit.visit_last_action_time >= ?
				AND log_visit.visit_last_action_time <= ?
				AND log_visit.idsite = ?
				AND location_ip_protocol IS NOT NULL
				GROUP BY location_ip_protocol";

		$bind = array($archiveProcessing->getStartDatetimeUTC(),
			$archiveProcessing->getEndDatetimeUTC(), $archiveProcessing->idsite);

		$query = $archiveProcessing->getSegment()->getSelectQuery($select, $from, $where, $bind);
		$rowSet = $archiveProcessing->db->query($query['sql'], $query['bind']);

		$data = array(
			'IPv6Usage_IPv4' => 0,
			'IPv6Usage_IPv6' => 0
		);

		while($row = $rowSet->fetch())
		{
			$key = sprintf("%s%d", 'IPv6Usage_IPv', $row['location_ip_protocol']);
			$data[$key] = $row['count'];
		}

		foreach($data as $key => $value)
		{
			$archiveProcessing->insertNumericRecord($key, $value);
		}
	}
}
