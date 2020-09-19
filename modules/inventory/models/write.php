<?php
/**
 * @filesource modules/inventory/models/write.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Write;

use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=inventory-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int $id ID
     *
     * @return object|null
     */
    public static function get($id)
    {
        if (empty($id)) {
            // ใหม่
            return (object) array(
                'id' => 0,
                'equipment' => '',
                'serial' => '',
                'detail' => '',
                'member_id' => 0,
                'device_user' => '',
                'create_date' => date('Y-m-d'),
                'status' => 1,
            );
        } else {
            // แก้ไข อ่านรายการที่เลือก
            $query = static::createQuery()
                ->from('inventory R')
                ->join('user U', 'LEFT', array('U.id', 'R.member_id'))
                ->where(array('R.id', $id));
            $select = array('R.*', 'U.name device_user');
            $n = 1;
            foreach (Language::get('INVENTORY_CATEGORIES') as $key => $label) {
                $query->join('inventory_meta M'.$n, 'LEFT', array(array('M'.$n.'.inventory_id', 'R.id'), array('M'.$n.'.name', $key)));
                $select[] = 'M'.$n.'.value '.$key;
                ++$n;
            }

            return $query->first($select);
        }
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (write.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, can_manage_inventory, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_manage_inventory') && Login::notDemoMode($login)) {
                try {
                    // รับค่าจากการ POST
                    $save = array(
                        'equipment' => $request->post('equipment')->topic(),
                        'serial' => $request->post('serial')->topic(),
                        'detail' => $request->post('detail')->textarea(),
                        'create_date' => $request->post('create_date')->date(),
                        'status' => $request->post('status')->toInt(),
                        'member_id' => $request->post('member_id')->toInt(),
                    );
                    $metas = array();
                    foreach (Language::get('INVENTORY_CATEGORIES') as $key => $label) {
                        $metas[$key] = $request->post($key)->topic();
                    }
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('id')->toInt());
                    if ($index) {
                        if ($save['serial'] == '') {
                            // ไม่ได้กรอก serial
                            $ret['ret_serial'] = 'Please fill in';
                        } else {
                            // ค้นหา serial ซ้ำ
                            $search = $this->db()->first($this->getTableName('inventory'), array('serial', $save['serial']));
                            if ($search && ($index->id == 0 || $index->id != $search->id)) {
                                $ret['ret_serial'] = Language::replace('This :name already exist', array(':name' => Language::get('Serial/Registration number')));
                            }
                        }
                        if ($save['equipment'] == '') {
                            // ไม่ได้กรอก equipment
                            $ret['ret_equipment'] = 'Please fill in';
                        }
                        if (empty($ret)) {
                            // Database
                            $db = $this->db();
                            // table
                            $table = $this->getTableName('inventory');
                            if ($index->id == 0) {
                                $save['id'] = $db->getNextId($table);
                            } else {
                                $save['id'] = $index->id;
                            }
                            // อัปโหลดไฟล์
                            $dir = ROOT_PATH.DATA_FOLDER.'inventory/';
                            foreach ($request->getUploadedFiles() as $item => $file) {
                                /* @var $file \Kotchasan\Http\UploadedFile */
                                if ($item == 'picture') {
                                    if ($file->hasUploadFile()) {
                                        if (!File::makeDirectory($dir)) {
                                            // ไดเรคทอรี่ไม่สามารถสร้างได้
                                            $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'inventory/');
                                        } else {
                                            try {
                                                $file->resizeImage(array('jpg', 'jpeg', 'png'), $dir, $save['id'].'.jpg', self::$cfg->inventory_w);
                                            } catch (\Exception $exc) {
                                                // ไม่สามารถอัปโหลดได้
                                                $ret['ret_'.$item] = Language::get($exc->getMessage());
                                            }
                                        }
                                    } elseif ($file->hasError()) {
                                        // ข้อผิดพลาดการอัปโหลด
                                        $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                                    }
                                }
                            }
                        }
                        if (empty($ret)) {
                            if ($index->id == 0) {
                                // ใหม่
                                $save['create_date'] = date('Y-m-d H:i:s');
                                $db->insert($table, $save);
                            } else {
                                // แก้ไข
                                $db->update($table, $index->id, $save);
                            }
                            // อัปเดต meta
                            $inventory_meta = $this->getTableName('inventory_meta');
                            $db->delete($inventory_meta, array('inventory_id', $save['id']), 0);
                            foreach ($metas as $key => $value) {
                                if ($value != '') {
                                    $db->insert($inventory_meta, array(
                                        'inventory_id' => $index->id,
                                        'name' => $key,
                                        'value' => $value,
                                    ));
                                }
                            }
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'inventory-setup'));
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
