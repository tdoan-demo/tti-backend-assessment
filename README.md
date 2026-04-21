# TTI Backend Engineer Assessment - Patient Reported Outcomes (PRO) API

This repository contains a Laravel 13 implementation of the Patient Reported Outcomes (PRO) API. It implements the required PRO API using Laravel migrations, Eloquent models, Form Requests, API Resources, service classes, seed data, and automated tests.

## What is included

- Normalized schema with indexes and foreign keys
- RESTful API endpoints for patients, instruments, submissions, and summaries
- Form Request validation with domain-level submission validation
- API Resources for consistent response shapes
- Service classes for submission creation and summary aggregation
- Named read/write rate limiters applied at the route layer using Laravel's built-in rate limiting system
- Seed data for local testing
- Feature tests covering key endpoints and unit tests for answer validation, normalization, and casting
- Docker Compose setup for local development
- Scramble-powered OpenAPI / Swagger documentation exposed at `/docs/api` and `/docs/api.json`

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
  - `submission_id`
  - `instrument_question_id`
  - `answer_value`
  - timestamps
  - unique index on (`submission_id`, `instrument_question_id`)

## Setup

1. Clone the repository and move into the project directory.
2. Install PHP dependencies with `composer install`.
3. Copy `.env.example` to `.env`.
4. Configure `.env` for MySQL by uncommenting and filling in `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` with your local MySQL credentials. The project expects a local MySQL database. `SESSION_DRIVER=file` and `CACHE_STORE=file` are used in `.env.example` to keep local setup friction low for this API-focused assessment.
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

### Docker Compose (optional)

A Docker Compose setup is included for a reproducible local environment with PHP and MySQL configured together. The Docker workflow uses `.env.docker.example` automatically, including Docker-safe database settings and file-backed cache for smoother first boot behavior.

1. Start the containers:

```bash
docker compose up --build -d
```

2. Run the database setup inside the app container:

```bash
docker compose exec app php artisan migrate --seed
```

3. The API will be available at `http://localhost:8000`. MySQL is exposed on host port `3307` with database `tti_pro_api`, user `laravel`, and password `secret`.

4. To run the test suite inside Docker:

```bash
docker compose exec app php artisan test
```

## API routes

The seeded sample data is intended for local exploration. The request examples below are written as a clean create flow and should use the ids returned by the preceding API calls rather than assuming fixed ids.

### Create patient

`POST /api/patients`

```json
{
  "name": "README Demo Patient",
  "date_of_birth": "1991-06-12",
  "mrn": "MRN-README-001"
}
```

### Create instrument

`POST /api/instruments`

```json
{
  "title": "README Demo Instrument",
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

Use the `id` returned from the patient response as `{patient_id}`. Use the `id` returned from the instrument response as `{instrument_id}`. Use the returned question ids as `{question_id_1}`, `{question_id_2}`, and `{question_id_3}`.

### Create submission

`POST /api/patients/{patient_id}/submissions`

```json
{
  "instrument_id": "{instrument_id}",
  "submitted_at": "2026-04-19T19:30:00Z",
  "answers": [
    {
      "question_id": "{question_id_1}",
      "answer": 4
    },
    {
      "question_id": "{question_id_2}",
      "answer": true
    },
    {
      "question_id": "{question_id_3}",
      "answer": "Fatigue improved after changing dose timing."
    }
  ]
}
```

### List submissions

`GET /api/patients/{patient_id}/submissions`

### Show a submission

`GET /api/patients/{patient_id}/submissions/{submission}`

### Summary

`GET /api/patients/{patient_id}/summary?instrument_id={instrument_id}`

### OpenAPI / Swagger documentation

Swagger-style API documentation is generated with [dedoc/scramble](https://scramble.dedoc.co/), which analyzes Laravel routes, Form Requests, and API Resources without requiring handwritten controller annotations.

After installing Composer dependencies, the docs are available at:

- `GET /docs/api` — interactive documentation UI
- `GET /docs/api.json` — generated OpenAPI JSON document

To export a static OpenAPI document for sharing or versioning:

```bash
php artisan scramble:export
```

## Design decisions and trade-offs

- **Questions and answers are modeled as separate relational tables** instead of JSON blobs so the schema stays normalized and the summary endpoint remains queryable and easy to reason about.

- **`answer_value` is stored in a single column** rather than separate typed columns. This keeps the schema smaller for the assessment and keeps the submission flow straightforward, while leaving type enforcement in the validation layer. The trade-off is that the database itself is less strongly typed for answer storage.

- **`submitted_at` is modeled explicitly on submissions** rather than inferred from `created_at`. This keeps the API aligned with the domain requirement that an instrument is completed at a specific date and time, and it allows seed data and tests to exercise time-based behavior such as newest-first ordering and summary date ranges. The trade-off is that the API accepts a client-provided timestamp, which is useful for seeded or imported data and would likely need tighter controls in a production setting.

- **Submission creation and summary aggregation are separated into service classes** to keep controllers focused on request/response orchestration. This keeps the business logic easier to follow, test, and extend.

- **Eager loading is used for nested submission responses and summary aggregation** to avoid N+1 query behavior when loading related instruments, questions, and answers. The schema also includes targeted indexes to support common lookups such as instrument-based queries and patient submission history, and submission listing is paginated to keep read responses bounded. The trade-off is that the current summary implementation still favors readability over heavier query-level aggregation or cached summary projections.

- **Submission validation uses custom Rule classes** for question membership and answer type checks. This keeps the request layer more readable and makes the domain rules easier to test and evolve independently. The trade-off is additional validation classes compared with keeping all logic inline in a single Form Request.

- **Factories are used for domain models in tests and seed setup** to keep record creation consistent and reusable. This reduces duplication and makes test setup easier to extend as the domain grows. The trade-off is a slightly larger supporting code surface compared with creating all records inline.

- **Named read/write rate limiters are applied at the route layer** using Laravel's built-in rate limiting system. This keeps throttling concerns out of controllers and makes the policy easy to adjust centrally. The trade-off is that the current limits are config-driven but still static rather than environment-specific per deployment tier.

- **`.env.example` uses `SESSION_DRIVER=file` and `CACHE_STORE=file`** to keep local setup friction low for this API-focused assessment. This avoids requiring additional sessions or cache tables that are outside the core scope of the exercise. The trade-off is that this favors fast local bootstrapping over database-backed session and cache storage.

- **Instrument versioning is intentionally omitted** to keep the solution aligned with the exercise scope. In a production system, edited instruments would likely require versioning or question snapshots to fully preserve the meaning of historical submissions.

## What I would add with more time

- Authentication and authorization, likely using Laravel Sanctum, so patient and submission data can be scoped to authenticated users and roles. I chose to implement rate limiting for this assessment’s optional API-hardening path and would treat auth as the next step in a fuller application.
- Instrument versioning or question snapshots so historical submissions remain semantically stable if an instrument changes later.
- More standardized API error formatting so validation, not-found, and domain errors follow a more consistent response shape.
- Additional test coverage around larger summary aggregation scenarios, malformed payload edge cases, and broader request-contract validation.
