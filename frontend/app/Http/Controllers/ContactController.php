<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Services\ApiClient;
use App\Services\ApiUnavailableException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        private readonly ApiClient $apiClient,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        try {
            $contacts = $this->apiClient->listContacts(
                $request->only(['name', 'city', 'country']),
                (int) $request->input('page', 0)
            );

            $filterOptions = $this->apiClient->getFilterOptions();
        } catch (ApiUnavailableException) {
            return response()->view('errors.500', [], 500);
        }

        return view('contacts.index', [
            'contacts' => $contacts['content'] ?? [],
            'pages' => $contacts['totalPages'] ?? 0,
            'filterOptions' => $filterOptions,
            'filters' => $request->only(['name', 'city', 'country']),
        ]);
    }

    public function show(int $id): View|RedirectResponse
    {
        try {
            $contact = $this->apiClient->getContact($id);
        } catch (ApiUnavailableException) {
            return response()->view('errors.500', [], 500);
        }

        return view('contacts.show', ['contact' => $contact]);
    }

    public function create(): View
    {
        return view('contacts.form', [
            'contact' => null,
            'action' => route('contacts.store'),
            'method' => 'POST',
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        try {
            $result = $this->apiClient->createContact($request->validated());
        } catch (ApiUnavailableException) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Service unavailable. Please try again later.']);
        }

        return redirect()->route('contacts.index');
    }

    public function edit(int $id): View|RedirectResponse
    {
        try {
            $contact = $this->apiClient->getContact($id);
        } catch (ApiUnavailableException) {
            return response()->view('errors.500', [], 500);
        }

        return view('contacts.form', [
            'contact' => $contact,
            'action' => route('contacts.update', $id),
            'method' => 'PUT',
        ]);
    }

    public function update(int $id, ContactRequest $request): RedirectResponse
    {
        try {
            $this->apiClient->updateContact($id, $request->validated());
        } catch (ApiUnavailableException) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Service unavailable. Please try again later.']);
        }

        return redirect()->route('contacts.show', $id);
    }
}
