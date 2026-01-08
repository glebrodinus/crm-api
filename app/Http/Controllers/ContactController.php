<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contacts = Contact::with('account')
            ->whereHas('account', fn ($q) => $q->where('owner_user_id', Auth::id()))
            ->get();

        $message = $contacts->isEmpty()
            ? 'No contacts found'
            : 'Contacts retrieved successfully';

        return $this->success($message, $contacts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id'      => 'required|exists:accounts,id',
            'first_name'      => 'required|string|max:50',
            'last_name'       => 'nullable|string|max:50',
            'phone'           => 'nullable|string|max:20',
            'phone_extension' => 'nullable|string|max:10',
            'mobile'          => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
        ]);

        // Authorize via AccountPolicy (owner check)
        $account = Account::findOrFail($data['account_id']);
        $this->authorize('update', $account);

        $contact = Contact::create($data);

        return $this->success(
            'Contact created successfully',
            $contact,
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $contact = Contact::with('account')->findOrFail($id);

        $this->authorize('view', $contact);

        return $this->success('Contact retrieved successfully', $contact);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $contact = Contact::with('account')->findOrFail($id);

        $this->authorize('update', $contact);

        $data = $request->validate([
            'first_name'      => 'sometimes|required|string|max:50',
            'last_name'       => 'nullable|string|max:50',
            'phone'           => 'nullable|string|max:20',
            'phone_extension' => 'nullable|string|max:10',
            'mobile'          => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
        ]);

        $contact->update($data);

        return $this->success(
            'Contact updated successfully',
            $contact->refresh()
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contact = Contact::with('account')->findOrFail($id);

        $this->authorize('delete', $contact);

        $contact->delete();

        return $this->success('Contact deleted successfully');
    }
}