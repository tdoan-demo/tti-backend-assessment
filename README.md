# TTI Backend Engineer Assessment - Patient Reported Outcomes (PRO) API

This repository contains a Laravel 13 implementation of the Patient Reported Outcomes (PRO) API. It implements the required PRO API using Laravel migrations, Eloquent models, Form Requests, API Resources, service classes, seed data, and feature tests.

## What is included

- Normalized schema with indexes and foreign keys
- RESTful API endpoints for patients, instruments, submissions, and summaries
- Form Request validation with domain-level submission validation
- API Resources for consistent response shapes
- Service classes for submission creation and summary aggregation
- Seed data for local testing
- Feature tests covering key endpoints

## Schema design

### Tables

- `patients`
  - `id`
  - `name`
  - `date_of_birth`
  - `mrn` (unique)
  - timestamps

- `instruments`
  - `id`
  - `title`
  - `description`
  - timestamps

- `instrument_questions`
  - `id`
  - `instrument_id` (FK)
  - `prompt`
  - `response_type` (`scale_1_5`, `yes_no`, `free_text`)
  - `sort_order`
  - timestamps
  - unique index on (`instrument_id`, `sort_order`)

- `submissions`
  - `id`
  - `patient_id` (FK)
  - `instrument_id` (FK)
  - `submitted_at`
  - timestamps
  - index on `instrument_id`
  - composite index on (`patient_id`, `submitted_at`)

- `submission_answers`
  - `id`
  - `submission_id` (FK)
  - `instrument_question_id` (FK)
  - `answer_value`
  - timestamps
  - unique index on (`submission_id`, `instrument_question_id`)

### Design decisions

- Questions and answers are modeled as separate relational tables instead of JSON blobs so the schema stays normalized and the summary endpoint can be queried and reasoned about cleanly.
- `answer_value` is stored as text for simplicity. Type safety is enforced at the application layer using the instrument question's `response_type`.
- Nested route scoping is used for patient submissions so requests cannot fetch a submission outside the parent patient context.

## Setup

1. Clone the repository and move into the project directory.
2. Install PHP dependencies with `composer install`.
3. Copy `.env.example` to `.env`.
4. Configure `.env` for MySQL by uncommenting and filling in `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` with your local MySQL credentials. The project expects a local MySQL database. `SESSION_DRIVER=file` is used in `.env.example` to keep local setup friction low for this API-focused assessment.
5. Generate an application key with `php artisan key:generate`.
6. Run:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

7. Run tests:

```bash
php artisan test
```

## API routes

### Create patient

`POST /api/patients`

```json
{
  "name": "Ava Chen",
  "date_of_birth": "1991-06-12",
  "mrn": "MRN-10001"
}
```

### Create instrument

`POST /api/instruments`

```json
{
  "title": "Weekly Symptom Check-In",
  "description": "A short weekly symptom and quality of life assessment.",
  "questions": [
    {
      "prompt": "Rate your fatigue this week.",
      "response_type": "scale_1_5",
      "sort_order": 1
    },
    {
      "prompt": "Did you experience nausea?",
      "response_type": "yes_no",
      "sort_order": 2
    },
    {
      "prompt": "Anything else you want your care team to know?",
      "response_type": "free_text",
      "sort_order": 3
    }
  ]
}
```

### Create submission

`POST /api/patients/{patient}/submissions`

```json
{
  "instrument_id": 1,
  "submitted_at": "2026-04-19T19:30:00Z",
  "answers": [
    {
      "question_id": 1,
      "answer": 4
    },
    {
      "question_id": 2,
      "answer": true
    },
    {
      "question_id": 3,
      "answer": "Fatigue improved after changing dose timing."
    }
  ]
}
```

### List submissions

`GET /api/patients/{patient}/submissions`

### Show a submission

`GET /api/patients/{patient}/submissions/{submission}`

### Summary

`GET /api/patients/{patient}/summary?instrument_id=1`

## Trade-offs

- Instrument versioning is intentionally omitted to keep the solution aligned with the exercise scope. In a production system, edited instruments would likely require versioning or question snapshots to fully preserve historical semantics.
- Summary aggregation is implemented in a service layer with eager loading for readability. If the dataset grows substantially, the next step would be pushing more aggregation directly into grouped SQL queries or cached summary projections.
- `answer_value` is stored in a single column rather than separate typed columns. This keeps the schema smaller for the assessment and keeps the API implementation straightforward, while leaving type enforcement in the validation layer.

## What I would add with more time

- OpenAPI documentation
- Auth and rate limiting scaffolding
- Stronger API error formatting standardization in exception handlers
- Instrument versioning / question snapshots for historical fidelity
- More granular unit tests around summary aggregation helpers
- Docker Compose for reproducible local setup
