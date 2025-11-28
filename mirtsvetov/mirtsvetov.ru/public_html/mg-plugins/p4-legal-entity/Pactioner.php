<?php

/**
 * 
 * Класс Pactioner предназначен для выполнения действий, AJAX запросов плагина.
 * 
 */
class Pactioner extends Actioner
{
	/**
     * 
	 * Импортирует юридические лица и адреса доставок в БД.
	 * 
     * (CRON) curl --user-agent Cron -s
     * 
     * URL: #SITE#/ajaxrequest?mguniqueurl=action/import&pluginHandler=p4-legal-entity&actionerClass=Pactioner
     * 
     */
    public function import()
    {
		$pluginName = LegalEntity::$pluginName;
		
		require_once(__DIR__ . '/lib/import.php');
		$import = new LegalEntityImport($pluginName);
		
		$files = [
			'companies' => [
				'path' => SITE_DIR . '/uploads/temp/tempcml/companies.csv',
				'name' => 'companies'
			],
			'addresses' => [
				'path' => SITE_DIR . '/uploads/temp/tempcml/addresses.csv',
				'name' => 'addresses'
			]
		];

		$path = SITE_DIR .'/uploads/temp/tempcml/csv/';

		$companies = $import->importFromCsv($files['companies']['path']);
		$companies = $import->rebuildCompaniesArray($companies);
		$import->startUploadLegalEntities($companies);
		
		$addresses = $import->importFromCsv($files['addresses']['path']);
		$addresses = $import->rebuildAddressesArray($addresses);
		$import->startUploadAddresses($addresses);

		//$import->copyFiles($path, $files);
		$import->deleteFiles($files);
    }
	
    /**
	 * 
	 * Устанавливает количество отображаемых записей на странице плагина.
	 * 
	 * @return bool
	 * 
	 */
	public function setCountPrintRowsEnity()
	{
		$count = 10;
		
		if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
			$count = $_POST['count'];
		}
		
		MG::setOption(array('option' => $_POST['option'], 'value' => $count));
		
		return true;
	}
}