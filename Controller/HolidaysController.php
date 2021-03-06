<?php
/**
 * Holidays Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Allcreator <info@allcreator.net>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */
App::uses('CakeTime', 'Utility');
App::uses('HolidaysAppController', 'Holidays.Controller');

/**
 * Holidays Controller
 *
 * @author Allcreator <info@allcreator.net>
 * @package NetCommons\Holidays\Controller
 */
class HolidaysController extends HolidaysAppController {

/**
 * use model
 *
 * @var array
 */
	public $uses = array(
		'M17n.Language',
		'Holidays.Holiday'
	);

/**
 * use component
 *
 * @var array
 */
	public $components = array(
		'M17n.SwitchLanguage' => array(
			'fields' => array(
				'Holiday.title'
			)
		),
		'Holidays.Holidays',
		'NetCommons.Permission' => array(
			'type' => PermissionComponent::CHECK_TYPE_SYSTEM_PLUGIN,
			'allow' => array()
		),
	);

/**
 * use helpers
 *
 * @var array
 */
	public $helpers = array(
		'NetCommons.NetCommonsTime',
		'NetCommons.Date',
		'NetCommons.Button',
		'NetCommons.NetCommonsHtml',
		'NetCommons.TableList'
	);

/**
 * Called before the controller action. You can use this method to configure and customize components
 * or perform logic that needs to happen before each controller action.
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#request-life-cycle-callbacks
 */
	public function beforeFilter() {
		parent::beforeFilter();
	}

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$targetYear = null;
		// 指定年取り出し
		if (isset($this->params['named']['targetYear'])) {
			$targetYear = $this->params['named']['targetYear'];
		} else {
			$targetYear = CakeTime::format((new NetCommonsTime())->getNowDatetime(), '%Y');
		}
		// 祝日設定リスト取り出し
		$holidays = $this->Holiday->getHolidayInYear($targetYear);
		// View変数設定
		$this->set('holidays', $holidays);
		$this->set('targetYear', $targetYear);
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			//他言語が入力されていない場合、表示されている言語データをセット
			$this->SwitchLanguage->setM17nRequestValue();

			// 登録処理
			if (! $this->HolidayRrule->saveHolidayRrule($this->request->data)) {
				$this->NetCommons->handleValidationError($this->HolidayRrule->validationErrors);
				return;
			}
			// 登録正常時
			$this->redirect('/holidays/holidays/index/');
			return;
		}
		// デフォルトデータ取り出し
		$data = $this->HolidayRrule->getDefaultData();
		$holiday = $this->Holiday->create();
		// 新規登録画面表示
		$this->request->data = $data;

		$langs = $this->Language->getLanguage();
		foreach ($langs as $lang) {
			$langId = $lang['Language']['id'];
			$holiday['Holiday']['language_id'] = $langId;
			$this->request->data['Holiday'][$langId] = $holiday['Holiday'];
		}
	}

/**
 * edit method
 *
 * @param int $rruleId Holiday rule id
 * @return void
 */
	public function edit($rruleId = null) {
		// EditのときはPUTでくる
		if ($this->request->is('put')) {
			//他言語が入力されていない場合、表示されている言語データをセット
			$this->SwitchLanguage->setM17nRequestValue();

			// 登録処理
			if (! $this->HolidayRrule->saveHolidayRrule($this->request->data)) {
				$this->NetCommons->handleValidationError($this->HolidayRrule->validationErrors);
				//$this->NetCommons->handleValidationError($this->Holiday->validationErrors);
				return;
			}
			// 登録正常時
			$this->redirect('/holidays/holidays/index/');
			return;
		}
		// ruleIdの指定がない場合エラー
		if ($rruleId <= 0) {
			$this->throwBadRequest();
			return false;
		}
		// データ取り出し
		$rrule = $this->HolidayRrule->find('first', array(
			'conditions' => array(
				'HolidayRrule.id' => $rruleId,
					),
		));
		// データがない場合エラー
		if (!$rrule) {
			$this->throwBadRequest();
			return false;
		}

		$holiday = $this->Holiday->find('all', array(
			'conditions' => array(
				'holiday_rrule_id' => $rruleId,
				'is_substitute' => false,
			),
		));
		$holiday = Hash::combine($holiday, '{n}.Holiday.language_id', '{n}.Holiday');

		// 編集画面表示
		$this->request->data = $rrule;
		$this->request->data['Holiday'] = $holiday;
	}

/**
 * delete method
 *
 * @param int $rruleId Holiday rule id
 * @throws NotFoundException
 * @return void
 */
	public function delete($rruleId = null) {
		if (! $this->request->is('delete')) { // test
			$this->throwBadRequest();
			return;
		}
		// ruleIdの指定がない場合エラー
		if ($rruleId <= 0) {
			$this->throwBadRequest();
			return false;
		}
		// 削除処理
		if (!$this->Holiday->deleteHoliday($rruleId)) {
			$this->throwBadRequest();
			return;
		}

		// 画面再表示
		// FUJI 削除しましたのFlashメッセージを設定してから
		$this->NetCommons->setFlashNotification(
			__d('holidays', 'Successfully deleted.'), array('class' => 'success')
		);
		// 画面再表示
		$this->redirect('/holidays/holidays/index/');
	}
}
