<?php

namespace App\Http\Controllers;

use App\Models\AttributeAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AttributeAttributeValueController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'attribute_id' => 'required|exists:attributes,id',
            'attribute_value_id' => 'required|array',
            'attribute_value_id.*' => 'exists:attribute_values,id',
        ]);

        $attributeId = $request->input('attribute_id');
        $attributeValueIds = $request->input('attribute_value_id');

        foreach ($attributeValueIds as $valueId) {
            AttributeAttributeValue::firstOrCreate([
                'attribute_id' => $attributeId,
                'attribute_value_id' => $valueId,
            ]);
        }

        return Redirect::route('voyager.attribute-attribute-values.index')
            ->with(['message' => 'Values saved successfully!', 'alert-type' => 'success']);
    }
}
