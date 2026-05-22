package com.demo.app.service;

import com.demo.app.domain.Contact;
import org.springframework.stereotype.Component;

@Component
public class NoOpContactEventPublisher implements ContactEventPublisher {

    @Override
    public void contactCreated(Contact contact) {
        // Phase 2: replace with Kafka producer implementation
    }

    @Override
    public void contactUpdated(Contact contact) {
        // Phase 2: replace with Kafka producer implementation
    }
}
