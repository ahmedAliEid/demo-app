package com.demo.app.repository;

import com.demo.app.domain.Address;
import com.demo.app.domain.Contact;
import com.demo.app.domain.User;
import org.springframework.data.jpa.domain.Specification;

public class ContactSpecifications {

    private ContactSpecifications() {}

    public static Specification<Contact> hasNameLike(String name) {
        return (root, query, cb) ->
                cb.like(cb.lower(root.get("fullName")), "%" + name.toLowerCase() + "%");
    }

    public static Specification<Contact> hasCity(String city) {
        return (root, query, cb) -> {
            var addressJoin = root.join("address");
            return cb.equal(cb.lower(addressJoin.get("city")), city.toLowerCase());
        };
    }

    public static Specification<Contact> hasCountry(String country) {
        return (root, query, cb) -> {
            var addressJoin = root.join("address");
            return cb.equal(cb.lower(addressJoin.get("country")), country.toLowerCase());
        };
    }

    public static Specification<Contact> ownedBy(User user) {
        return (root, query, cb) -> cb.equal(root.get("owner"), user);
    }
}
