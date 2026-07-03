# Employees Module

Status: Baseline finalized

---

## Purpose

Manage employee profiles, departments, shifts, attendance, documents, salary
details, and emergency contacts.

---

## Scope

Included:

- Employee profile.
- Department.
- Shift.
- Attendance.
- Documents.
- Salary details.
- Emergency contact.
- Active or inactive status.

---

## Core Rules

- Employee records belong to a hotel unless configured as group-level staff.
- Salary details require restricted permissions.
- Employee documents must use protected storage.
- Attendance changes should be traceable.
- Inactive employees should not be assignable to new tasks.

---

## Permissions

- `employees.view`
- `employees.manage`
- `employees.view_salary`
- `employees.manage_salary`
- `attendance.view`
- `attendance.manage`

---

## Audit Events

- Employee created.
- Employee updated.
- Salary changed.
- Attendance corrected.
- Document uploaded.
- Employee deactivated.

