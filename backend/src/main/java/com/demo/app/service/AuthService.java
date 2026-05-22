package com.demo.app.service;

import com.demo.app.domain.User;
import com.demo.app.dto.LoginRequest;
import com.demo.app.dto.LoginResponse;
import com.demo.app.security.JwtTokenProvider;
import com.demo.app.security.UserDetailsServiceImpl;
import org.springframework.security.authentication.BadCredentialsException;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;

@Service
public class AuthService {

    private final UserDetailsServiceImpl userDetailsService;
    private final JwtTokenProvider jwtTokenProvider;
    private final PasswordEncoder passwordEncoder;

    public AuthService(UserDetailsServiceImpl userDetailsService,
                       JwtTokenProvider jwtTokenProvider,
                       PasswordEncoder passwordEncoder) {
        this.userDetailsService = userDetailsService;
        this.jwtTokenProvider = jwtTokenProvider;
        this.passwordEncoder = passwordEncoder;
    }

    public LoginResponse login(LoginRequest req) {
        User user;
        try {
            user = (User) userDetailsService.loadUserByUsername(req.username());
        } catch (Exception e) {
            throw new BadCredentialsException("Invalid username or password");
        }
        if (!passwordEncoder.matches(req.password(), user.getPassword())) {
            throw new BadCredentialsException("Invalid username or password");
        }
        String token = jwtTokenProvider.generateToken(user);
        return new LoginResponse(token, jwtTokenProvider.getExpirySeconds(),
                user.getRole().name().toLowerCase());
    }
}
