<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models;

class AddressController extends Controller
{
    public function createOrUpdate() {
        $addressId = $this->param('addressId', 'nullable|int', null);
        $address = $this->param('address', 'required|string');
        $latitude = $this->param('latitude', 'required|numeric');
        $longitude = $this->check('longitude', 'required|numeric');

        $isExist = Models\Address::where([['latitude', $latitude], ['longitude', $longitude]])->exists();
        if ($isExist) {
            return $this->error(303);
        }

        if ($addressId) {
            Models\Address::where('id', $addressId)->update([
                'address' => $address,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        } else {
            $addressObj = Models\Address::create([
                'address' => $address,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
            $addressId = $addressObj->id;
        }

        return $this->output(['addressId' => $addressId]);
    }

    public function list() {
        $addressColl = Models\Address::get();
        $addressList = [];
        foreach($addressColl as $addressObj) {
            $addressList[] = [
                'addressId' => $addressObj->id,
                'address' => $addressObj->address,
                'latitude' => $addressObj->latitude,
                'longitude' => $addressObj->longitude,
            ];
        }

        return $this->output(['addressList' => $addressList]);
    }
}
