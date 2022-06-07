<?php


namespace App\Services;


use App\Models\Equipment;
use App\Models\EquipmentType;
use Illuminate\Support\Str;

class EquipmentService
{
    private $rules = [
            'N' => '0-1',
            'A' => 'A-Z',
            'a' => 'a-z',
            'X' => 'a-z0-9',
            'Z' => '(?i)(\W|^)(-|_|@)(\W|$)'
    ];

    public function getAll()
    {
        return Equipment::all();
    }

    public function get($id)
    {
        return Equipment::whereId($id)->first();
    }

    public function create(array $data)
    {
        $serials = $data['serial'];
        $error_array = [];
        $equipmentType = EquipmentType::where(['code' => $data['code_type']])->first();
        if (!$equipmentType) {
            throw new \Exception('Code ' . $data['code_type'] . 'not found');
        }

        $result_array = [];


        foreach ($serials as $serial) {
            $equipmentType = EquipmentType::where(['code' => $data['code_type']])->first();
            if (!$equipmentType) {
                throw new \Exception('Code ' . $data['code_type'] . 'not found');
            }

            $equemnet = Equipment::where(['serial' => $serial])->first();
            if ($equemnet) {
                $error_array[]['serial'] = 'Equipment with serial already exist';
            }

            $errors = $this->validate($serial, $equipmentType->serial_mask);

            if ($errors) {
                //    throw  new \Exception(implode(',',$errors));
            }
            $result_array[] = [
                    'code' => $data['code'],
                    'serial' => $serial,
                    'comment' => $data['comment'],
                    'equipmentType' => $data['code_type']
            ];
        }

        foreach ($result_array as $item) {
            $equipment = new Equipment();
            $equipment->code = $item['code'];
            $equipment->serial = $item['serial'];
            $equipment->comment = $item['comment'];
            $equipmentType = EquipmentType::where(['code' => $item['equipmentType']])->firstOrFail();
            $equipment->equipmentType()->associate($equipmentType);
            $equipment->save();
        }
    }

    public function validate($serial, $mask)
    {
        $errors = [];
        foreach (str_split($serial) as $key => $char) {
            $result = preg_match('/[' . $this->rules[$mask[$key]] . ']/', $char);


            if ($result !== 0) {
                $errors[$key] = $mask[$key];
            }
        }
        return $errors;
    }

    public function update(Equipment $equipment, array $data)
    {
        $serials = $data['serial'];

        $equipmentType = EquipmentType::where(['code' => $data['code_type']])->first();
        if (!$equipmentType) {
            throw new \Exception('Code ' . $data['code_type'] . 'not found');
        }

        $result_array = [];


        $equipmentType = EquipmentType::where(['code' => $data['code_type']])->first();
        if (!$equipmentType) {
            throw new \Exception('Code ' . $data['code_type'] . 'not found');
        }


        $errors = $this->validate($serials, $equipmentType->serial_mask);

        if ($errors) {
            //    throw  new \Exception(implode(',',$errors));
        }
        $result_array[] = [
                'code' => $data['code'],
                'serial' => $serials,
                'comment' => $data['comment'],
                'equipmentType' => $data['code_type']
        ];


        $equipment->code = $data['code'];
        $equipment->serial = $data['serial'];
        $equipment->comment = $data['comment'];
        $equipmentType = EquipmentType::where(['code' => $data['code_type']])->firstOrFail();
        $equipment->equipmentType()->associate($equipmentType);
        $equipment->save();
        return $equipment->refresh();
    }
}
