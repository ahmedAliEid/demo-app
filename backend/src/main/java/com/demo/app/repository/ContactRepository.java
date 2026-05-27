package com.demo.app.repository;

import com.demo.app.domain.Contact;
import com.demo.app.domain.User;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.JpaSpecificationExecutor;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.List;

public interface ContactRepository extends JpaRepository<Contact, Long>,
        JpaSpecificationExecutor<Contact> {

    boolean existsByFullNameIgnoreCaseAndOwner(String fullName, User owner);

    boolean existsByFullNameIgnoreCase(String fullName);

    @Query("SELECT DISTINCT a.city FROM Address a JOIN a.contact c " +
           "WHERE (:owner IS NULL OR c.owner = :owner) ORDER BY a.city")
    List<String> findDistinctCities(@Param("owner") User owner);

    @Query("SELECT DISTINCT a.country FROM Address a JOIN a.contact c " +
           "WHERE (:owner IS NULL OR c.owner = :owner) ORDER BY a.country")
    List<String> findDistinctCountries(@Param("owner") User owner);
}
