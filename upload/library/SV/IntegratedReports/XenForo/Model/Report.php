<?php

class SV_IntegratedReports_XenForo_Model_Report extends XFCP_SV_IntegratedReports_XenForo_Model_Report
{
	public function getReportHandlers()
	{
		$classes = $this->getContentTypesWithField('report_handler_class');
		$handlers = array();
		foreach ($classes AS $contentType => $class)
		{
			if (!class_exists($class))
			{
				continue;
			}

            $class = XenForo_Application::resolveDynamicClass($class);
			$handlers[$contentType] = new $class();
		}
		return $handlers;
	}    
    
    
	public function getVisibleReportsForUser(array $reports, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return array();
		}

		$reportsGrouped = array();
		foreach ($reports AS $reportId => $report)
		{
			$reportsGrouped[$report['content_type']][$reportId] = $report;
		}

		if (!$reportsGrouped)
		{
			return array();
		}

		$reportHandlers = $this->getReportHandlers();

		$userReports = array();
		foreach ($reportsGrouped AS $contentType => $typeReports)
		{
			if (!empty($reportHandlers[$contentType]))
			{
				$handler = $reportHandlers[$contentType];

				$typeReports = $handler->getVisibleReportsForUser($typeReports, $viewingUser);
				$userReports += $handler->prepareReports($typeReports);
			}
		}

		$outputReports = array();
		foreach ($reports AS $reportId => $null)
		{
			if (isset($userReports[$reportId]))
			{
				$outputReports[$reportId] = $userReports[$reportId];
				$outputReports[$reportId]['isVisible'] = true;
			}
		}

		return $outputReports;
	}    
}