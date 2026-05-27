package com.demo.app.controller;

import com.demo.app.dto.*;
import com.demo.app.service.ContactService;
import jakarta.validation.Valid;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.web.bind.annotation.*;

import java.net.URI;

@RestController
@RequestMapping("/api/v1/contacts")
public class ContactController {

    private final ContactService contactService;

    public ContactController(ContactService contactService) {
        this.contactService = contactService;
    }

    @GetMapping
    public ResponseEntity<PagedResponse<ContactSummary>> listContacts(
            @RequestParam(required = false) String name,
            @RequestParam(required = false) String city,
            @RequestParam(required = false) String country,
            @RequestParam(defaultValue = "0") int page,
            @RequestParam(defaultValue = "20") int size,
            Authentication auth) {
        return ResponseEntity.ok(contactService.listContacts(name, city, country, page, size, auth));
    }

    @GetMapping("/filter-options")
    public ResponseEntity<FilterOptions> getFilterOptions(Authentication auth) {
        return ResponseEntity.ok(contactService.getFilterOptions(auth));
    }

    @PostMapping
    public ResponseEntity<ContactResponse> createContact(
            @RequestBody @Valid ContactRequest req,
            Authentication auth) {
        ContactResponse response = contactService.createContact(req, auth);
        URI location = URI.create("/api/v1/contacts/" + response.id());
        return ResponseEntity.created(location).body(response);
    }

    @GetMapping("/{id}")
    public ResponseEntity<ContactResponse> getContact(@PathVariable Long id,
                                                       Authentication auth) {
        return ResponseEntity.ok(contactService.getContact(id, auth));
    }

    @PutMapping("/{id}")
    public ResponseEntity<ContactResponse> updateContact(@PathVariable Long id,
                                                          @RequestBody @Valid ContactRequest req,
                                                          Authentication auth) {
        return ResponseEntity.ok(contactService.updateContact(id, req, auth));
    }
}
