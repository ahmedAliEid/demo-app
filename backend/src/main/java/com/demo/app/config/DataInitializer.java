package com.demo.app.config;

import com.demo.app.domain.Role;
import com.demo.app.domain.User;
import com.demo.app.repository.UserRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.boot.ApplicationArguments;
import org.springframework.boot.ApplicationRunner;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Component;

@Component
public class DataInitializer implements ApplicationRunner {

    private static final Logger log = LoggerFactory.getLogger(DataInitializer.class);

    private final UserRepository userRepository;
    private final PasswordEncoder passwordEncoder;

    public DataInitializer(UserRepository userRepository, PasswordEncoder passwordEncoder) {
        this.userRepository = userRepository;
        this.passwordEncoder = passwordEncoder;
    }

    @Override
    public void run(ApplicationArguments args) {
        if (userRepository.findByUsername("admin").isEmpty()) {
            User admin = new User();
            admin.setUsername("admin");
            admin.setPasswordHash(passwordEncoder.encode("admin123"));
            admin.setRole(Role.ADMIN);
            userRepository.save(admin);
            log.info("Created default admin user");
        } else {
            // Always re-encode to ensure hash is valid for the current encoder
            userRepository.findByUsername("admin").ifPresent(admin -> {
                admin.setPasswordHash(passwordEncoder.encode("admin123"));
                userRepository.save(admin);
                log.info("Re-encoded admin password with current encoder");
            });
        }
    }
}
