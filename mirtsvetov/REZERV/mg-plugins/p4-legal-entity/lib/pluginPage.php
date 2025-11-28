<?php

class LegalEntityPluginPage
{
    private static $pluginName = null;
	private static $path = null;
	private static $lang = [];

    public function __construct($pluginName, $path, $lang)
	{
		$this->pluginName = $pluginName;
		$this->path = $path;
		$this->lang = $lang;
    }

    /**
	 * 
	 * Страница плагина в панели управления.
	 * 
	 */
	public function pluginPage()
	{
		$pluginName = $this->pluginName;
		$path = $this->path;
		$lang = $this->lang;
		
		$currentPage = $_COOKIE[$pluginName.'_tab'];

		if ($currentPage === '1' || !isset($currentPage)) {
			$userFilter = $this->filterForUsersPage();
			$filter = new Filter($userFilter['property']);
			$sql = LegalEntity::$legalEntity->getSqlForUsersPage($userFilter['filter']);
		}

		if ($currentPage === '2') {
			$userFilter = $this->filterForLegalEntitiesPage();
			$filter = new Filter($userFilter['property']);
			$sql = LegalEntity::$legalEntity->getSqlForLegalEntitiesPage($userFilter['filter']);
		}

		if ($currentPage === '3') {
			$userFilter = $this->filterForSessionPage();
			$filter = new Filter($userFilter['property']);
			$sql = LegalEntity::$session->getSqlForSessionPage($userFilter['filter']);
		}

		$page = !empty($_POST['page']) ? $_POST['page'] : 0;
		$countPrintRows = MG::getSetting($pluginName . '-countPrintRows') != null ? MG::getSetting($pluginName . '-countPrintRows') : 10;

		$navigator = new Navigator($sql, $page, $countPrintRows);
		
		$entities = $navigator->getRowsSql();

		$pagination = $navigator->getPager('forAjax');

		$filter = $filter->getHtmlFilterAdmin();
		$itemsCount = $navigator->getNumRowsSql();

		echo '
			<link rel="stylesheet" href="'. SITE .'/'. $path .'/css/admin/style.css" type="text/css">
			<link rel="stylesheet" href="'. SITE .'/'. $path .'/css/admin/modal.css" type="text/css">
			<link rel="stylesheet" href="'. SITE .'/'. $path .'/css/admin/timepicker.min.css" type="text/css">
			<script>
				includeJS("'. SITE .'/'. $path .'/js/admin/script.js");
				includeJS("'. SITE .'/'. $path .'/js/admin/validation.js");
				includeJS("'. SITE .'/'. $path .'/js/admin/userBind.js");
				includeJS("'. SITE .'/'. $path .'/js/admin/legalEntity.js");
				includeJS("'. SITE .'/'. $path .'/js/admin/legalEntity.debt.js");
				includeJS("'. SITE .'/'. $path .'/js/admin/legalEntity.address.js");
				includeJS("'. SITE .'/'. $path .'/js/admin/legalEntity.session.js");
				includeJS("'. SITE .'/'. $path .'/js/admin/jquery-ui-timepicker-addon.js");
			</script> 
		';

		include($path . '/views/admin/page/pluginPage.php');
	}

    /**
	 * 
	 * Фильтр для страницы "Пользователи".
	 * 
	 * @return array
	 * 
	 */
	private function filterForUsersPage() 
	{
		$lang = $this->lang;
		$display = false;

		$property = array(
			'user_login_phone' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_LOGIN_PHONE'],
				'value' => !empty($_POST['user_login_phone']) ? trim($_POST['user_login_phone']) : null,
			),
			'user_contact_phone' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_CONTACT_PHONE'],
				'value' => !empty($_POST['user_contact_phone']) ? trim($_POST['user_contact_phone']) : null,
			),
			'user_login_email' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_LOGIN_EMAIL'],
				'value' => !empty($_POST['user_login_email']) ? trim($_POST['user_login_email']) : null,
			),
			'user_contact_email' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_CONTACT_EMAIL'],
				'value' => !empty($_POST['user_contact_email']) ? trim($_POST['user_contact_email']) : null,
			),
			'sorter' => array(
				'type' => 'hidden',
				'label' => $lang['FILTER_SORT'],
				'value' => !empty($_POST['sorter']) ? $_POST['sorter'] : null,
			),
		);

		if (isset($_POST['applyFilter'])) {
			$property['applyFilter'] = array(
				'type' => 'hidden',
				'label' => $lang['FILTER_APPLY'],
				'value' => 1,
			);
			$display = true;
		}

		if (!empty($_POST['user_login_phone'])) {
			$arFilter['`login_phone`'] = array(
				trim($_POST['user_login_phone']), 'like'
			);
		}

		if (!empty($_POST['user_contact_phone'])) {
			$arFilter['`phone`'] = array(
				trim($_POST['user_contact_phone']), 'like'
			);
		}

		if (!empty($_POST['user_login_email'])) {
			$arFilter['`login_email`'] = array(
				trim($_POST['user_login_email']), 'like'
			);
		}

		if (!empty($_POST['user_contact_email'])) {
			$arFilter['`email`'] = array(
				trim($_POST['user_contact_email']), 'like'
			);
		}

		$filter = new Filter($property);
		$filter = $filter->getFilterSql($arFilter);

		if (empty($_POST['sorter'])) {
			if (empty($filter)) {
				$filter .= ' 1=1 ';
			}
			$filter .= ' ORDER BY `id` DESC';
		} else {
			$sorterData = explode('|', $_POST['sorter']);
			if ($sorterData[1] > 0) {
				$sorterData[3] = 'ASC';
			} else {
				$sorterData[3] = 'DESC';
			}
			if (empty($filter)) {
				$filter .= ' 1=1';
			}
			$filter .= ' ORDER BY '. DB::quote($sorterData[0], true) .' '. DB::quote($sorterData[3], true);
		}

		$result['property'] = $property;
		$result['display'] = $display;
		$result['filter'] = $filter;

		return $result;
	}

	/**
	 * 
	 * Фильтр для страницы "Юридические лица".
	 * 
	 * @return array
	 * 
	 */
	private function filterForLegalEntitiesPage()
	{
		$lang = $this->lang;
		$display = false;

		$property = array(
			'user_login_phone' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_LOGIN_PHONE'],
				'value' => !empty($_POST['user_login_phone']) ? trim($_POST['user_login_phone']) : null,
			),
			'user_contact_phone' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_CONTACT_PHONE'],
				'value' => !empty($_POST['user_contact_phone']) ? trim($_POST['user_contact_phone']) : null,
			),
			'user_login_email' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_LOGIN_EMAIL'],
				'value' => !empty($_POST['user_login_email']) ? trim($_POST['user_login_email']) : null,
			),
			'user_contact_email' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_CONTACT_EMAIL'],
				'value' => !empty($_POST['user_contact_email']) ? trim($_POST['user_contact_email']) : null,
			),

			'legal_entity_name' => array(
				'type' => 'text',
				'label' => $lang['FILTER_LEGAL_ENTITY_NAME'],
				'value' => !empty($_POST['legal_entity_name']) ? trim($_POST['legal_entity_name']) : null,
			),
			'legal_entity_kpp' => array(
				'type' => 'text',
				'label' => $lang['FILTER_LEGAL_ENTITY_KPP'],
				'value' => !empty($_POST['legal_entity_kpp']) ? trim($_POST['legal_entity_kpp']) : null,
			),
			'legal_entity_inn' => array(
				'type' => 'text',
				'label' => $lang['FILTER_LEGAL_ENTITY_INN'],
				'value' => !empty($_POST['legal_entity_inn']) ? trim($_POST['legal_entity_inn']) : null,
			),

			'sorter' => array(
				'type' => 'hidden',
				'label' => $lang['FILTER_SORT'],
				'value' => !empty($_POST['sorter']) ? $_POST['sorter'] : null,
			),
		);

		if (isset($_POST['applyFilter'])) {
			$property['applyFilter'] = array(
				'type' => 'hidden',
				'label' => $lang['FILTER_APPLY'],
				'value' => 1,
			);
			$display = true;
		}

		if (!empty($_POST['user_login_phone'])) {
			$arFilter['u.`login_phone`'] = array(
				trim($_POST['user_login_phone']), 'like'
			);
		}

		if (!empty($_POST['user_contact_phone'])) {
			$arFilter['u.`phone`'] = array(
				trim($_POST['user_contact_phone']), 'like'
			);
		}

		if (!empty($_POST['user_login_email'])) {
			$arFilter['u.`login_email`'] = array(
				trim($_POST['user_login_email']), 'like'
			);
		}

		if (!empty($_POST['user_contact_email'])) {
			$arFilter['u.`email`'] = array(
				trim($_POST['user_contact_email']), 'like'
			);
		}

		if (!empty($_POST['legal_entity_name'])) {
			$arFilter['le.`name`'] = array(
				trim($_POST['legal_entity_name']), 'like'
			);
		}

		if (!empty($_POST['legal_entity_inn'])) {
			$arFilter['le.`inn`'] = array(
				trim($_POST['legal_entity_inn']), 'like'
			);
		}

		if (!empty($_POST['legal_entity_kpp'])) {
			$arFilter['le.`kpp`'] = array(
				trim($_POST['legal_entity_kpp']), 'like'
			);
		}

		$filter = new Filter($property);
		$filter = $filter->getFilterSql($arFilter);

		if (empty($_POST['sorter'])) {
			if (empty($filter)) {
				$filter .= ' 1=1 ';
			}
			$filter .= ' ORDER BY `id` DESC';
		} else {
			$sorterData = explode('|', $_POST['sorter']);
			if ($sorterData[1] > 0) {
				$sorterData[3] = 'ASC';
			} else {
				$sorterData[3] = 'DESC';
			}
			if (empty($filter)) {
				$filter .= ' 1=1';
			}
			$filter .= ' ORDER BY '. DB::quote($sorterData[0], true) .' '. DB::quote($sorterData[3], true);
		}

		$result['property'] = $property;
		$result['display'] = $display;
		$result['filter'] = $filter;

		return $result;
	}

	/**
	 * 
	 * Фильтр для страницы "Сессии".
	 * 
	 * @return array
	 * 
	 */
	private function filterForSessionPage()
	{
		$lang = $this->lang;
		$display = false;

		$property = array(
			'user_login_phone' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_LOGIN_PHONE'],
				'value' => !empty($_POST['user_login_phone']) ? trim($_POST['user_login_phone']) : null,
			),
			'user_contact_phone' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_CONTACT_PHONE'],
				'value' => !empty($_POST['user_contact_phone']) ? trim($_POST['user_contact_phone']) : null,
			),
			'user_login_email' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_LOGIN_EMAIL'],
				'value' => !empty($_POST['user_login_email']) ? trim($_POST['user_login_email']) : null,
			),
			'user_contact_email' => array(
				'type' => 'text',
				'label' => $lang['FILTER_USER_CONTACT_EMAIL'],
				'value' => !empty($_POST['user_contact_email']) ? trim($_POST['user_contact_email']) : null,
			),
			'sorter' => array(
				'type' => 'hidden',
				'label' => $lang['FILTER_SORT'],
				'value' => !empty($_POST['sorter']) ? $_POST['sorter'] : null,
			),
		);

		if (isset($_POST['applyFilter'])) {
			$property['applyFilter'] = array(
				'type' => 'hidden',
				'label' => $lang['FILTER_APPLY'],
				'value' => 1,
			);
			$display = true;
		}

		if (!empty($_POST['user_login_phone'])) {
			$arFilter['u.`login_phone`'] = array(
				trim($_POST['user_login_phone']), 'like'
			);
		}

		if (!empty($_POST['user_contact_phone'])) {
			$arFilter['u.`phone`'] = array(
				trim($_POST['user_contact_phone']), 'like'
			);
		}

		if (!empty($_POST['user_login_email'])) {
			$arFilter['u.`login_email`'] = array(
				trim($_POST['user_login_email']), 'like'
			);
		}

		if (!empty($_POST['user_contact_email'])) {
			$arFilter['u.`email`'] = array(
				trim($_POST['user_contact_email']), 'like'
			);
		}

		$filter = new Filter($property);
		$filter = $filter->getFilterSql($arFilter);

		if (empty($_POST['sorter'])) {
			if (empty($filter)) {
				$filter .= ' 1=1 ';
			}
			$filter .= ' ORDER BY `id` DESC';
		} else {
			$sorterData = explode('|', $_POST['sorter']);
			if ($sorterData[1] > 0) {
				$sorterData[3] = 'ASC';
			} else {
				$sorterData[3] = 'DESC';
			}
			if (empty($filter)) {
				$filter .= ' 1=1';
			}
			$filter .= ' ORDER BY '. DB::quote($sorterData[0], true) .' '. DB::quote($sorterData[3], true);
		}

		$result['property'] = $property;
		$result['display'] = $display;
		$result['filter'] = $filter;

		return $result;
	}
}