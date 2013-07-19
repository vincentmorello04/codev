<?php

require('../include/session.inc.php');
require('../path.inc.php');

class TimeReportController extends Controller {

	public static function staticInit() {
		// nothing
	}

	protected function display() {

		// only authenticated users
		if (!Tools::isConnectedUser()) {
			return; // show login dialog
		}

		// only team & observers have access
		if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
			$this->smartyHelper->assign('accessDenied', TRUE);
			return;
		}

		// only if the team has projects
		$team = TeamCache::getInstance()->getTeam($this->teamid);
		if (count($team->getProjects(false)) <= 0) {
			return;
		}

		// whether to include the leaves project
		$includeLeaves = "on" == Tools::getSecurePOSTStringValue("includeLeaves", "");
		$includeLeavesChecked = $includeLeaves ? "checked=\"on\"" : "";
		$this->smartyHelper->assign('includeLeavesChecked', $includeLeavesChecked);

		// date range defaults to current month
		$month = date('m');
		$year = date('Y');

		$startDate = Tools::getSecurePOSTStringValue("startdate", Tools::formatDate("%Y-%m-%d", mktime(0, 0, 0, $month, 1, $year)));
		$this->smartyHelper->assign('startDate', $startDate);
		$startTimestamp = Tools::date2timestamp($startDate);

		$nbDaysInMonth = date("t", $startTimestamp);
		$endDate = Tools::getSecurePOSTStringValue("enddate", Tools::formatDate("%Y-%m-%d", mktime(0, 0, 0, $month, $nbDaysInMonth, $year)));
		$this->smartyHelper->assign('endDate', $endDate);
		$endTimestamp = Tools::date2timestamp($endDate);

		// time spent on projects
		$timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $this->teamid);
		$this->smartyHelper->assign('timeTracking', $timeTracking);

		$workingDays = $this->getWorkingDaysPerProjectPerUser($timeTracking, $includeLeaves);
		$this->smartyHelper->assign('workingDays', $workingDays);

		// warnings
		$consistencyErrors = $this->getConsistencyErrors($timeTracking);
		if (count($consistencyErrors) > 0) {
			$this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
			$this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors) . ' ' . T_("Errors"));
			$this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors) . ' ' . T_("days are incomplete or undefined"));
		}
	}

	/**
	 * Calculates the working days per user and project.
	 */
	private function getWorkingDaysPerProjectPerUser(TimeTracking $timeTracking, $includeLeaves) {
		$includeDisabledProjects = true;
		return $timeTracking->getWorkingDaysPerProjectPerUser($includeLeaves, $includeDisabledProjects);
	}

	/**
	 * Calculates the consistency errors excluding future errors.
	 */
	private
	function getConsistencyErrors(TimeTracking $timeTracking) {
		$consistencyErrors = array();
		$errorList = ConsistencyCheck2::checkIncompleteDays($timeTracking);
		if (count($errorList) > 0) {
			foreach ($errorList as $error) {
				$this->session_user = UserCache::getInstance()->getUser($error->userId);
				$consistencyErrors[] = array(
					'date' => date("Y-m-d", $error->timestamp),
					'user' => $this->session_user->getRealname(),
					'severity' => $error->getLiteralSeverity(),
					'severityColor' => $error->getSeverityColor(),
					'desc' => $error->desc);
			}
		}
		return $consistencyErrors;
	}
}

TimeReportController::staticInit();
$controller = new TimeReportController('../', 'Time Report', 'ProdReports');
$controller->execute();

?>
