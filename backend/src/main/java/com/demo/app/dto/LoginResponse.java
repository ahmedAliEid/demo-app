package com.demo.app.dto;

public record LoginResponse(
        String token,
        long expiresIn,
        String role
) {}
