package com.demo.app.exception;

public class DuplicateContactNameException extends RuntimeException {
    public DuplicateContactNameException(String name) {
        super("A contact named '" + name + "' already exists");
    }
}
