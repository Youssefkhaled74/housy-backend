<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
   public function storeContactUs(Request $request)
    {
        $contact = new Contact;
        $contact->name = $request->first_name;
        $contact->last_name = $request->last_name;
        $contact->email = $request->email;
        $contact->phone = $request->phone;
        $contact->subject = $request->subject;
        $contact->content = $request->content;
        $contact->save();

        return response()->json([
            'result' => true,
            'data'=>$contact,
            'message' => translate('contact us has been added successfully')
        ]);
    }
}
