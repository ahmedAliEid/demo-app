package com.demo.app.domain;

import jakarta.persistence.*;

import java.time.Instant;

@Entity
@Table(name = "contacts")
public class Contact {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "full_name", nullable = false, length = 255)
    private String fullName;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "owner_id", nullable = false)
    private User owner;

    @OneToOne(mappedBy = "contact", cascade = CascadeType.ALL,
              fetch = FetchType.LAZY, orphanRemoval = true)
    private Address address;

    @Column(name = "created_at", nullable = false, updatable = false)
    private Instant createdAt;

    @Column(name = "updated_at", nullable = false)
    private Instant updatedAt;

    @PrePersist
    void onCreate() {
        createdAt = updatedAt = Instant.now();
    }

    @PreUpdate
    void onUpdate() {
        updatedAt = Instant.now();
    }

    public Long getId() { return id; }
    public String getFullName() { return fullName; }
    public User getOwner() { return owner; }
    public Address getAddress() { return address; }
    public Instant getCreatedAt() { return createdAt; }
    public Instant getUpdatedAt() { return updatedAt; }

    public void setFullName(String fullName) { this.fullName = fullName; }
    public void setOwner(User owner) { this.owner = owner; }
    public void setAddress(Address address) { this.address = address; }
    public void setUpdatedAt(Instant updatedAt) { this.updatedAt = updatedAt; }
}
