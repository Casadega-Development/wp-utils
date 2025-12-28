# register_rest_route `args`

Below is a **field-by-field catalogue** of every keyword that WordPress (6.5) recognises inside an _argument schema_ array (`'args'` for `register_rest_route()` or the objects returned by controller helpers such as `get_collection_params()`).
Think of it as two concentric circles:

<br />

| Layer                                                                 | What it contains |
| --------------------------------------------------------------------- | ---------------- |
| **A – WordPress-only extensions**  *(security, DX, meta)*             |                  |
| **B – JSON Schema Draft-4 subset**  *(all the real validation rules)* |                  |

If you supply a key that isn’t listed here it will be ignored (not an error), so you can still add doc-comment style notes if you wish.

<br />

## [A] WordPress-specific keywords

| Key                 | Type       | Purpose                                                                                                                                                     |                                                                                                    |
| ------------------- | ---------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| `description`       | `string`   | Human-readable help shown in the route index.([developer.wordpress.org][1])                                                                                 |                                                                                                    |
| `default`           | *mixed*    | Fills in the parameter when it was omitted from the request (sanitisation still runs).                                                                      |                                                                                                    |
| `required`          | `bool`     | If `true`, WordPress returns `400` when the param is missing. Works at both param level and as v3-style object property flag.([developer.wordpress.org][1]) |                                                                                                    |
| `context`           | \`string   | array` (`view`, `edit`, `embed\`)                                                                                                                           | Lets the same field appear in multiple “views” of a response object.([developer.wordpress.org][1]) |
| `readonly`          | `bool`     | Marks a field as output-only; WordPress will ignore it on input.([developer.wordpress.org][1])                                                              |                                                                                                    |
| `validate_callback` | `callable` | Custom validation. Runs **before** JSON-Schema validation.([developer.wordpress.org][2])                                                                    |                                                                                                    |
| `sanitize_callback` | `callable` | Custom sanitiser. Runs after validation.([developer.wordpress.org][2])                                                                                      |                                                                                                    |
| `prepare_callback`  | `callable` | Last-chance filter that runs *after* sanitisation just before the value is stored on the request.                                                           |                                                                                                    |
| `arg_options`       | `array`    | Controller helpers sometimes inject this to override `sanitize_callback`/`validate_callback` for a field generated from `get_item_schema()`.                |                                                                                                    |

[1]: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/ "Schema – REST API Handbook | Developer.WordPress.org"
[2]: https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/ "Routes and Endpoints – REST API Handbook | Developer.WordPress.org"
<br />

## [B] JSON Schema Draft-4 keywords (implemented in core)

> Tip: To allow a nullable value, include 'null' in the type array instead of using a separate flag.

### Universal / type-agnostic

| Keyword             | What it does                                                                                                                         |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| `type` *(required)* | Primitive or array of primitives: `string`, `integer`, `number`, `boolean`, `array`, `object`, `null`.([developer.wordpress.org][1]) |
| `enum`              | Limits to a fixed set of values.([developer.wordpress.org][2])                                                                       |
| `format`            | Extra validation for strings: `date-time`, `uri`, `email`, `ip`, `uuid`, `hex-color`.([developer.wordpress.org][1])                  |
| `oneOf`, `anyOf`    | Composite schemas (match exactly one / at least one).([developer.wordpress.org][1])                                                  |
| `title`, `$schema`  | Metadata—ignored by the validator but kept for discovery.                                                                            |

[1]: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/ "Schema – REST API Handbook | Developer.WordPress.org"
[2]: https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/ "Routes and Endpoints – REST API Handbook | Developer.WordPress.org"

### String-only

| Keyword                  | Notes                                                             |
| ------------------------ | ----------------------------------------------------------------- |
| `minLength`, `maxLength` | Inclusive character counts.([developer.wordpress.org][1])         |
| `pattern`                | ECMA-262 regex (not auto-anchored).([developer.wordpress.org][1]) |

[1]: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/ "Schema – REST API Handbook | Developer.WordPress.org"

### Number & integer

| Keyword                                | Notes                                                                               |
| -------------------------------------- | ----------------------------------------------------------------------------------- |
| `minimum`, `maximum`                   | Inclusive range.([developer.wordpress.org][1])                                      |
| `exclusiveMinimum`, `exclusiveMaximum` | Switch to *strict* inequality.([developer.wordpress.org][1])                        |
| `multipleOf`                           | Value must be an exact multiple (accepts floats too).([developer.wordpress.org][1]) |

[1]: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/ "Schema – REST API Handbook | Developer.WordPress.org"

### Array

| Keyword                | Notes                                                                                      |
| ---------------------- | ------------------------------------------------------------------------------------------ |
| `items`                | Schema each element must satisfy (can itself be any schema).([developer.wordpress.org][1]) |
| `minItems`, `maxItems` | Cardinality limits.([developer.wordpress.org][1])                                          |
| `uniqueItems`          | Enforces all-unique elements *after* sanitisation.([developer.wordpress.org][1])           |

[1]: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/ "Schema – REST API Handbook | Developer.WordPress.org"

### Object

| Keyword                          | Notes                                                                                                                                   |
| -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| `properties`                     | Map of sub-schemas.([developer.wordpress.org][1])                                                                                       |
| `required`                       | **v3 style** (`'required'=>true` inside each property) *or* **v4 style** (`'required'=>[ 'foo','bar' ]`).([developer.wordpress.org][1]) |
| `additionalProperties`           | `false` to forbid unknown keys *or* a schema all extra keys must match.([developer.wordpress.org][1])                                   |
| `patternProperties`              | Regex-matched property names + schema.([developer.wordpress.org][1])                                                                    |
| `minProperties`, `maxProperties` | Object-size limits.([developer.wordpress.org][1])                                                                                       |

[1]: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/ "Schema – REST API Handbook | Developer.WordPress.org"

<br />

## Putting it all together — “cheat sheet” snippet

```php

$args = [
	'price' => [               // number example
		'description' => 'Unit price in USD',
		'type'        => 'number',
		'minimum'     => 0,
		'multipleOf'  => 0.01,
		'required'    => true,
	],

	'tags'  => [               // array example
		'type'        => 'array',
		'items'       => [ 'type' => 'string', 'maxLength' => 20 ],
		'maxItems'    => 5,
		'uniqueItems' => true,
	],

	'meta'  => [               // object example
		'type'                 => 'object',
		'properties'           => [
			'is_featured' => [ 'type' => 'boolean', 'default' => false ],
			'hex'         => [ 'type' => 'string', 'format' => 'hex-color' ],
		],
		'additionalProperties' => false,
	],

	'search' => [              // string with custom callbacks
		'type'              => 'string',
		'minLength'         => 3,
		'sanitize_callback' => 'sanitize_text_field',
		'validate_callback' => 'my_plugin_is_not_sql_injection',
	],
];
```

Everything above is a first-class, core-supported keyword; no hidden traps. Memorise the table and you have 100 % coverage of what register_rest_route() can do for request-parameter validation and sanitisation.

<br />

## Sanitization / Validation Helpers

### 1. Sanitisation helpers (sanitize_*, rest_sanitize_*, …)

| Target data            | Recommended function(s)                                                                               | Notes                                                                                                                       |                                                                            |
| ---------------------- | ----------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| **Free-form text**     | `sanitize_text_field()` · `sanitize_textarea_field()`                                                 | Strips tags, normalises whitespace; the textarea variant keeps line-breaks.([developer.wordpress.org][1])                   |                                                                            |
| **Slugs / keys**       | `sanitize_key()` · `sanitize_title()` · `sanitize_title_with_dashes()` · `sanitize_title_for_query()` | For URL slugs, option names, meta keys.([developer.wordpress.org][1])                                                       |                                                                            |
| **E-mail**             | `sanitize_email()`                                                                                    | Converts unicode, rejects invalid addresses.([developer.wordpress.org][1])                                                  |                                                                            |
| **URLs**               | `sanitize_url()` *(alias of `esc_url_raw()`)*                                                         | Leaves a raw URL safe for storage.([developer.wordpress.org][1])                                                            |                                                                            |
| **Colours**            | `sanitize_hex_color()` · `sanitize_hex_color_no_hash()`                                               | Validates `#rrggbb` or `rrggbb`.([developer.wordpress.org][1])                                                              |                                                                            |
| **CSS class names**    | `sanitize_html_class()`                                                                               | Allows only `A-Z a-z 0-9 -`.([developer.wordpress.org][1])                                                                  |                                                                            |
| **Filenames / MIME**   | `sanitize_file_name()` · `sanitize_mime_type()`                                                       | Cleans characters that break OSes and shell commands.([developer.wordpress.org][1])                                         |                                                                            |
| **SQL `ORDER BY`**     | `sanitize_sql_orderby()`                                                                              | Keeps only \`ASC                                                                                                            | DESC\` and column names; prevents injection.([developer.wordpress.org][1]) |
| **Terms / taxonomies** | `sanitize_term()` · `sanitize_term_field()`                                                           | Used internally when saving categories/tags.([developer.wordpress.org][1])                                                  |                                                                            |
| **Usernames**          | `sanitize_user()`                                                                                     | Removes unsafe chars, supports strict mode.([developer.wordpress.org][1])                                                   |                                                                            |
| **Any HTML blob**      | `wp_kses()` (configurable) · `wp_kses_post()` (WordPress-standard tag list)                           | Allows only whitelisted elements & attributes.([developer.wordpress.org][1])                                                |                                                                            |
| **REST booleans**      | `rest_sanitize_boolean()`                                                                             | Turns `1/0`, `'true'/'false'` into real bools.([developer.wordpress.org][2])                                                |                                                                            |
| **Schema-driven**      | `rest_sanitize_value_from_schema()` · `rest_sanitize_request_arg()`                                   | Auto-sanitise according to the same JSON-Schema you registered.([developer.wordpress.org][3], [developer.wordpress.org][4]) |                                                                            |

[1]: https://developer.wordpress.org/apis/security/sanitizing/ "Sanitizing Data – Common APIs Handbook | Developer.WordPress.org"
[2]: https://developer.wordpress.org/reference/functions/rest_sanitize_boolean/?utm_source=chatgpt.com "rest_sanitize_boolean() – Function - WordPress Developer Resources"
[3]: https://developer.wordpress.org/reference/functions/rest_sanitize_value_from_schema/ "rest_sanitize_value_from_schema() – Function | Developer.WordPress.org"
[4]: https://developer.wordpress.org/reference/functions/rest_sanitize_request_arg/?utm_source=chatgpt.com "rest_sanitize_request_arg() – Function | Developer.WordPress.org"

### 2. Validation helpers (is_*, *_exists, rest_validate_* …)

| Check you need              | Function                                                            | Returns                                                                                                                                      |                                                                  |
| --------------------------- | ------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| **E-mail syntax**           | `is_email()`                                                        | \`string                                                                                                                                     | false`﻿—valid address or `false\`.([developer.wordpress.org][1]) |
| **Boolean-ish input**       | `rest_is_boolean()`                                                 | `true/false` after checking strings & ints.([developer.wordpress.org][2])                                                                    |                                                                  |
| **Boolean (strict)**        | `wp_validate_boolean()`                                             | Filters to real `true/false`.([developer.wordpress.org][3])                                                                                  |                                                                  |
| **IP v4 / v6**              | `rest_is_ip_address()`                                              | Returns canonical IP or `false`.([developer.wordpress.org][4])                                                                               |                                                                  |
| **UUID (v1–v5)**            | `wp_is_uuid()`                                                      | `true/false`.([developer.wordpress.org][5])                                                                                                  |                                                                  |
| **File-path safety**        | `validate_file()`                                                   | `0` on success; *>0* error codes if the path tries directory traversal.([developer.wordpress.org][6])                                        |                                                                  |
| **Does user / term exist?** | `username_exists()` · `term_exists()`                               | ID on success, `null/0` otherwise.([developer.wordpress.org][6])                                                                             |                                                                  |
| **HTML balance**            | `balanceTags()` / `force_balance_tags()`                            | Returns well-formed markup.([developer.wordpress.org][6])                                                                                    |                                                                  |
| **REST schema**             | `rest_validate_value_from_schema()` · `rest_validate_request_arg()` | `true` or `WP_Error` after deep JSON-Schema checks (range, pattern, enum, etc.).([developer.wordpress.org][7], [developer.wordpress.org][6]) |                                                                  |

[1]: https://developer.wordpress.org/reference/functions/sanitize_text_field/?utm_source=chatgpt.com "sanitize_text_field() – Function - WordPress Developer Resources"
[2]: https://developer.wordpress.org/reference/functions/rest_is_boolean/ "rest_is_boolean() – Function | Developer.WordPress.org"
[3]: https://developer.wordpress.org/reference/functions/wp_validate_boolean/?utm_source=chatgpt.com "wp_validate_boolean() – Function | Developer.WordPress.org"
[4]: https://developer.wordpress.org/reference/functions/rest_is_ip_address/ "rest_is_ip_address() – Function | Developer.WordPress.org"
[5]: https://developer.wordpress.org/reference/functions/wp_is_uuid/?utm_source=chatgpt.com "wp_is_uuid() – Function | Developer.WordPress.org"
[6]: https://developer.wordpress.org/apis/security/data-validation/ "Validating Data – Common APIs Handbook | Developer.WordPress.org"
[7]: https://developer.wordpress.org/reference/functions/rest_validate_value_from_schema/ "rest_validate_value_from_schema() – Function | Developer.WordPress.org"

### 3. Putting them to work

```php
'args' => [
    'email' => [
        'type'              => 'string',
        'format'            => 'email',
        'sanitize_callback' => 'sanitize_email',
        'validate_callback' => 'is_email',
        'required'          => true,
    ],
    'active' => [
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'validate_callback' => 'rest_is_boolean',
    ],
    'uuid' => [
        'type'              => 'string',
        'validate_callback' => 'wp_is_uuid',
    ],
]
```
<br />
<hr />
<br />

### Quick tips

- Pair sanitisers with validators for defence-in-depth (e.g. `sanitize_text_field()` + regex validator).

- Keep callbacks small & pure—return only `true`, `false`, or `WP_Error`; avoid side-effects.

- REST schema helpers (`rest_*_from_schema`) already understand the JSON-Schema keywords (`enum`, `minLength`, `format`, …). Use them when a custom callback would only duplicate that work.

- If none of the core helpers match your need, supply your own anonymous function or `[ $this, 'method' ]` — just remember to return a `WP_Error` with a unique code when validation fails.

With this toolbox you rarely need to write more than a handful of lines to keep dodgy input out of your REST controllers.
