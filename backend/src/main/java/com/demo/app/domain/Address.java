package com.demo.app.domain;

import jakarta.persistence.*;

@Entity
@Table(name = "addresses")
public class Address {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @OneToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "contact_id", nullable = false, unique = true)
    private Contact contact;

    @Column(name = "street_line1", nullable = false, length = 255)
    private String streetLine1;

    @Column(name = "street_line2", length = 255)
    private String streetLine2;

    @Column(nullable = false, length = 100)
    private String city;

    @Column(name = "state_province", length = 100)
    private String stateProvince;

    @Column(name = "postal_code", length = 20)
    private String postalCode;

    @Column(nullable = false, length = 100)
    private String country;

    @Column(length = 50)
    private String phone;

    public Long getId() { return id; }
    public Contact getContact() { return contact; }
    public String getStreetLine1() { return streetLine1; }
    public String getStreetLine2() { return streetLine2; }
    public String getCity() { return city; }
    public String getStateProvince() { return stateProvince; }
    public String getPostalCode() { return postalCode; }
    public String getCountry() { return country; }
    public String getPhone() { return phone; }

    public void setContact(Contact contact) { this.contact = contact; }
    public void setStreetLine1(String streetLine1) { this.streetLine1 = streetLine1; }
    public void setStreetLine2(String streetLine2) { this.streetLine2 = streetLine2; }
    public void setCity(String city) { this.city = city; }
    public void setStateProvince(String stateProvince) { this.stateProvince = stateProvince; }
    public void setPostalCode(String postalCode) { this.postalCode = postalCode; }
    public void setCountry(String country) { this.country = country; }
    public void setPhone(String phone) { this.phone = phone; }
}
