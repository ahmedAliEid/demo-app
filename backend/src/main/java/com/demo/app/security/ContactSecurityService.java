package com.demo.app.security;

import com.demo.app.repository.ContactRepository;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Service;

@Service("contactSecurity")
public class ContactSecurityService {

    private final ContactRepository contactRepository;

    public ContactSecurityService(ContactRepository contactRepository) {
        this.contactRepository = contactRepository;
    }

    public boolean isOwner(Long contactId, Authentication auth) {
        return contactRepository.findById(contactId)
                .map(c -> c.getOwner().getUsername().equals(auth.getName()))
                .orElse(false);
    }
}
