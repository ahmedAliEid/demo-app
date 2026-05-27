package com.demo.app.dto;

import java.util.List;

public record FilterOptions(
        List<String> cities,
        List<String> countries
) {}
