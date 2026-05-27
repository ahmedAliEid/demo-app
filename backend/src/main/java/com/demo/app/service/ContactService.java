package com.demo.app.service;

import com.demo.app.domain.Address;
import com.demo.app.domain.Contact;
import com.demo.app.domain.Role;
import com.demo.app.domain.User;
import com.demo.app.dto.*;
import com.demo.app.exception.ContactNotFoundException;
import com.demo.app.exception.DuplicateContactNameException;
import com.demo.app.repository.ContactRepository;
import com.demo.app.repository.ContactSpecifications;
import com.demo.app.repository.UserRepository;
import com.demo.app.security.ContactSecurityService;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.jpa.domain.Specification;
import org.springframework.security.access.AccessDeniedException;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.time.Instant;

@Service
@Transactional
public class ContactService {

    private final ContactRepository contactRepository;
    private final UserRepository userRepository;
    private final ContactEventPublisher eventPublisher;
    private final ContactSecurityService contactSecurity;

    public ContactService(ContactRepository contactRepository,
                          UserRepository userRepository,
                          ContactEventPublisher eventPublisher,
                          ContactSecurityService contactSecurity) {
        this.contactRepository = contactRepository;
        this.userRepository = userRepository;
        this.eventPublisher = eventPublisher;
        this.contactSecurity = contactSecurity;
    }

    @Transactional(readOnly = true)
    public PagedResponse<ContactSummary> listContacts(String name, String city, String country,
                                                       int page, int size, Authentication auth) {
        User user = resolveUser(auth);
        boolean isAdmin = user.getRole() == Role.ADMIN;

        Specification<Contact> spec = Specification.where(
                isAdmin ? null : ContactSpecifications.ownedBy(user));

        if (StringUtils.hasText(name)) {
            spec = spec == null
                    ? ContactSpecifications.hasNameLike(name)
                    : spec.and(ContactSpecifications.hasNameLike(name));
        }
        if (StringUtils.hasText(city)) {
            spec = spec == null
                    ? ContactSpecifications.hasCity(city)
                    : spec.and(ContactSpecifications.hasCity(city));
        }
        if (StringUtils.hasText(country)) {
            spec = spec == null
                    ? ContactSpecifications.hasCountry(country)
                    : spec.and(ContactSpecifications.hasCountry(country));
        }

        Page<Contact> result = contactRepository.findAll(spec, PageRequest.of(page, size));
        return new PagedResponse<>(
                result.getContent().stream().map(ContactSummary::from).toList(),
                result.getNumber(),
                result.getSize(),
                result.getTotalElements(),
                result.getTotalPages()
        );
    }

    @Transactional(readOnly = true)
    public FilterOptions getFilterOptions(Authentication auth) {
        User user = resolveUser(auth);
        User scopeUser = user.getRole() == Role.ADMIN ? null : user;
        return new FilterOptions(
                contactRepository.findDistinctCities(scopeUser),
                contactRepository.findDistinctCountries(scopeUser)
        );
    }

    public ContactResponse createContact(ContactRequest req, Authentication auth) {
        User user = resolveUser(auth);
        checkDuplicate(req.fullName(), user);

        Contact contact = new Contact();
        contact.setFullName(req.fullName());
        contact.setOwner(user);

        Address address = buildAddress(req, contact);
        contact.setAddress(address);

        Contact saved = contactRepository.save(contact);
        eventPublisher.contactCreated(saved);
        return ContactResponse.from(saved);
    }

    @Transactional(readOnly = true)
    public ContactResponse getContact(Long id, Authentication auth) {
        Contact contact = contactRepository.findById(id)
                .orElseThrow(() -> new ContactNotFoundException(id));
        User user = resolveUser(auth);
        if (user.getRole() != Role.ADMIN && !contactSecurity.isOwner(id, auth)) {
            throw new AccessDeniedException("Forbidden");
        }
        return ContactResponse.from(contact);
    }

    public ContactResponse updateContact(Long id, ContactRequest req, Authentication auth) {
        Contact contact = contactRepository.findById(id)
                .orElseThrow(() -> new ContactNotFoundException(id));
        User user = resolveUser(auth);

        if (user.getRole() != Role.ADMIN && !contactSecurity.isOwner(id, auth)) {
            throw new AccessDeniedException("Forbidden");
        }

        if (!contact.getFullName().equalsIgnoreCase(req.fullName())) {
            checkDuplicate(req.fullName(), user);
        }

        contact.setFullName(req.fullName());
        contact.setUpdatedAt(Instant.now());

        Address address = contact.getAddress();
        if (address == null) {
            address = new Address();
            address.setContact(contact);
            contact.setAddress(address);
        }
        applyAddressFields(req, address);

        Contact saved = contactRepository.save(contact);
        eventPublisher.contactUpdated(saved);
        return ContactResponse.from(saved);
    }

    private void checkDuplicate(String fullName, User user) {
        boolean duplicate = user.getRole() == Role.ADMIN
                ? contactRepository.existsByFullNameIgnoreCase(fullName)
                : contactRepository.existsByFullNameIgnoreCaseAndOwner(fullName, user);
        if (duplicate) {
            throw new DuplicateContactNameException(fullName);
        }
    }

    private Address buildAddress(ContactRequest req, Contact contact) {
        Address address = new Address();
        address.setContact(contact);
        applyAddressFields(req, address);
        return address;
    }

    private void applyAddressFields(ContactRequest req, Address address) {
        address.setStreetLine1(req.streetLine1());
        address.setStreetLine2(req.streetLine2());
        address.setCity(req.city());
        address.setStateProvince(req.stateProvince());
        address.setPostalCode(req.postalCode());
        address.setCountry(req.country());
        address.setPhone(req.phone());
    }

    private User resolveUser(Authentication auth) {
        return userRepository.findByUsername(auth.getName())
                .orElseThrow(() -> new IllegalStateException("Authenticated user not found"));
    }
}
