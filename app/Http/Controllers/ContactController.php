<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(Request $request)
    {
        $data = $request->validate([
            // Contact fields
            'account_id'      => 'required|exists:accounts,id',
            'first_name'      => 'required|string|max:50',
            'last_name'       => 'nullable|string|max:50',
            'phone'           => 'nullable|string|max:20',
            'phone_extension' => 'nullable|string|max:10',
            'mobile'          => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
        ]);

        // Create account
        $contact = Contact::create($data);
        $contact->refresh();

        if (! $contact) {
            return $this->error('Failed to create contact', [], 500);
        }

        return $this->success('Contact created successfully', $contact, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
