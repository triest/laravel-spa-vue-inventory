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

    private $availableKeys = [
            'code_type',
            'comment',
            'serial',
            'code'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return Equipment::all();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function get($id)
    {
        return Equipment::whereId($id)->first();
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function create(array $data)
    {
        if ($fields = !empty(array_diff_key(array_flip($this->availableKeys), $data))) {
            throw new \Exception(
                    'Field is required:' . implode(
                            ',',
                            array_keys(array_diff_key(array_flip($this->availableKeys), $data))
                    )
            );
        }
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
                throw  new \Exception($errors);
            }
            $result_array[] = [
                    'code' => $data['code'],
                    'serial' => $serial,
                    'comment' => $data['comment'] ?? null,
                    'equipmentType' => $data['code_type']
            ];
        }
        $result_collection = [];
        foreach ($result_array as $item) {
            $equipment = new Equipment();
            $equipment->code = $item['code'];
            $equipment->serial = $item['serial'];
            $equipment->comment = $item['comment'];
            $equipmentType = EquipmentType::where(['code' => $item['equipmentType']])->firstOrFail();
            $equipment->equipmentType()->associate($equipmentType);
            $equipment->save();
            $result_collection[] = $equipment;
        }

        return $result_collection;
    }

    /**
     * @param $serial
     * @param $mask
     * @return array
     */
    public function validate($serial, $mask)
    {
        $errors = [];
        foreach (str_split($serial) as $key => $char) {
            preg_match('/[' . $this->rules[$mask[$key]] . ']/', $char, $matches);
            if (empty($matches)) {
                $errors[$char] = $this->rules[$mask[$key]];
            }
        }

        return $this->printErrors($errors);
    }

    /**
     * @param array $errors
     * @return array|string
     */
    public function printErrors(array $errors)
    {
        if (empty($errors)) {
            return $errors;
        }
        $flattened = $errors;
        array_walk(
                $flattened,
                function (&$value, $key) {
                    $value = "{$key}:{$value}";
                }
        );
        return implode(', ', $flattened);
    }

    /**
     * @param Equipment $equipment
     * @param array $data
     * @return Equipment
     * @throws \Exception
     */
    public function update(Equipment $equipment, array $data)
    {
        $serials = $data['serial'];
        $equipmentType = EquipmentType::where(['code' => $data['code_type']])->first();
        if (!$equipmentType) {
            throw new \Exception('Code ' . $data['code_type'] . ' not found');
        }

        $result_array = [];

        $errors = $this->validate($serials, $equipmentType->serial_mask);

        if ($errors) {
            throw  new \Exception($errors);
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

    /**
     * @param Equipment $equipment
     * @return bool|null
     */
    public function destroy(Equipment $equipment): ?bool
    {
        return $equipment->delete();
    }
}

