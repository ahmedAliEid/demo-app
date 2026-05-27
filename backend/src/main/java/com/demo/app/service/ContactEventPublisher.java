package com.demo.app.service;

import com.demo.app.domain.Contact;

public interface ContactEventPublisher {
    void contactCreated(Contact contact);
    void contactUpdated(Contact contact);
}
