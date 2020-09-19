<?php
/**
 * @filesource modules/repair/views/receive.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Repair\Receive;

use Kotchasan\Html;
use Kotchasan\Http\UploadedFile;

/**
 * เพิ่ม-แก้ไข แจ้งซ่อม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * module=repair-receive
     *
     * @param object $index
     * @param array $login
     *
     * @return string
     */
    public function render($index, $login)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/repair/model/receive/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true,
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Repair job description}',
        ));
        $groups = $fieldset->add('groups', array(
            'comment' => '{LNG_Find equipment by} {LNG_Equipment}, {LNG_Serial/Registration number}',
        ));
        // serial
        $groups->add('text', array(
            'id' => 'serial',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Serial/Registration number}',
            'maxlength' => 20,
            'value' => $index->serial,
        ));
        // equipment
        $groups->add('text', array(
            'id' => 'equipment',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'width50',
            'label' => '{LNG_Equipment}',
            'maxlength' => 64,
            'value' => $index->equipment,
        ));
        // inventory_id
        $fieldset->add('hidden', array(
            'id' => 'inventory_id',
            'value' => $index->inventory_id,
        ));
        // job_description
        $fieldset->add('textarea', array(
            'id' => 'job_description',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_problems and repairs details}',
            'rows' => 5,
            'value' => $index->job_description,
        ));
        if ($index->id == 0) {
            // comment
            $fieldset->add('text', array(
                'id' => 'comment',
                'labelClass' => 'g-input icon-comments',
                'itemClass' => 'item',
                'label' => '{LNG_Comment}',
                'comment' => '{LNG_Note or additional notes}',
                'maxlength' => 255,
                'value' => $index->comment,
            ));
            // status_id
            $fieldset->add('hidden', array(
                'id' => 'status_id',
                'value' => $index->status_id,
            ));
        }
        // files
        $fieldset->add('file', array(
            'id' => 'files',
            'name' => 'files[]',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Browse file}',
            'comment' => '{LNG_Browse image uploaded, type :type} ({LNG_Can select multiple files, total size not exceeding :size})',
            'multiple' => true,
            'dataPreview' => 'filePreview',
            'accept' => self::$cfg->repair_file_typies,
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit',
        ));
        // submit
        $fieldset->add('submit', array(
            'id' => 'save',
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}',
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'id',
            'value' => $index->id,
        ));
        \Gcms\Controller::$view->setContentsAfter(array(
            '/:type/' => implode(', ', self::$cfg->repair_file_typies),
            '/:size/' => UploadedFile::getUploadSize(),
        ));
        // Javascript
        $form->script('initRepairGet();');
        // คืนค่า HTML

        return $form->render();
    }
}
