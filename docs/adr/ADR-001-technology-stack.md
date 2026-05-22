# ADR-001: Technology Stack — WordPress, Java Spring Boot, Apache Kafka

**Date**: 2026-05-22
**Status**: Accepted

## Context

A demo application is required with a UI layer, a backend API layer, and an
asynchronous messaging layer. The project is delivered in two phases: Phase 1
focuses on a functional UI + REST API; Phase 2 introduces event-driven messaging.
The team has existing familiarity with WordPress for content/UI and Java for
backend services.

## Decision

- **UI Layer**: WordPress (PHP 8.1+, WordPress 6.4+). Handles all rendering,
  theming, and end-user interaction. Consumes the Spring Boot REST API; stores
  no business data itself.
- **Backend Layer**: Java Spring Boot 3.x on Java 21 (LTS). Owns all business
  logic, data persistence, and REST API exposure.
- **Messaging Layer**: Apache Kafka 3.x (Phase 2). Manages all asynchronous,
  event-driven communication between services once the REST layer is stable.

## Consequences

**Positive**:
- WordPress provides a mature, plugin-rich UI platform with minimal frontend
  build complexity.
- Spring Boot 3.x + Java 21 virtual threads give strong performance and a large
  ecosystem (Spring Security, Spring Data JPA, Spring Kafka).
- Kafka decouples services for Phase 2 without requiring Phase 1 refactoring,
  provided the Outbox/Event Publisher pattern is used from the start.

**Negative**:
- Two runtimes (PHP + JVM) require separate deployment pipelines and monitoring
  stacks.
- WordPress–Spring Boot integration relies entirely on REST; any latency in the
  API is directly visible to the WordPress UI.
- Kafka adds operational complexity (ZooKeeper/KRaft, Schema Registry, consumer
  lag monitoring) that is deferred to Phase 2 but must be accounted for in
  infrastructure planning.
