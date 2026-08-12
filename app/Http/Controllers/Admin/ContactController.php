<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ContactDataTable;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(protected ContactService $contactService)
    {
    }
     /**
     * Display a listing of the resource.
     */
    public function index(ContactDataTable $dataTable)
    {
        return $dataTable->render('dashboard.contacts.index');

    }

    public function show(Contact $contact)
    {
        return view('dashboard.contacts.show', ['contact' => $contact]);
    }

     /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
        $this->contactService->delete($contact);
        deleted();
        return back();
    }
}
