---
title: "Routing and middleware"
---

`App` is a CLI HTTP/1.1 server whose PHP handlers run in Asyncara Fibers. Register routes and middleware before calling `listen`. The listener owns the runtime and returns only after shutdown; do not call it from inside `Async::run`.

## Static, dynamic and grouped routes

```php
use Asyncara\App;
use Asyncara\Server\{Request, Response};
$app = new App();
$app->get('/status', fn() => Response::json(['status' => 'OK']));
$app->group('/api/v1', function (App $router): void {
    $router->get('/users/{id}', function (Request $request): Response {
        $id = filter_var($request->param('id'), FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) return Response::text('Invalid id', 400);
        return Response::json(['id' => $id]);
    });
});
$app->listen('127.0.0.1:8080');
```

The actual path is `/api/v1/users/42`. Static routes are preferred to dynamic routes; dynamic patterns are checked in registration order. `{id}` captures one nonempty path segment and percent-decodes the captured value. It is not a typed parameter, authorization decision or filesystem-safe path. Duplicate static routes and duplicate parameter names are rejected.

Convenience methods cover GET, POST, PUT, PATCH and DELETE. Use `route('OPTIONS', '/resource', $handler)` for another method. HEAD falls back to GET when no explicit HEAD route exists and suppresses the body. An unmatched route returns 404, including an unmatched method; do not assume an automatic 405/Allow response.

## Named middleware

Middleware receives the Request and the next callable. Return a Response or StreamResponse on every path. Register a name before `listen`, then attach it to routes:

```php
$app->middleware('auth', function (Request $request, callable $next): Response {
    $token = getenv('API_TOKEN');
    if (!is_string($token) || $token === '' ||
        !hash_equals('Bearer '.$token, $request->header('authorization', ''))) {
        return Response::json(['error' => 'unauthorized'], 401);
    }
    return $next($request);
});
$app->post('/private', fn() => Response::json(['accepted' => true]))->middleware('auth');
```

Use a union return type if downstream handlers can stream. Multiple attached middleware run in attachment order, wrapping the handler. A missing named middleware is a startup error. Grouping prefixes paths; it does not automatically attach authorization to every route. CORS, sessions, cookie policy and CSRF protection are application responsibilities.

## Long-lived application state

Create configuration and lazy connection pools before listen, but acquire native connections inside handlers or worker startup. Never retain Request objects in a global cache. PHP globals survive requests; Context has explicit request isolation, not automatic global cleanup. See the [REST API recipe](../recipes/rest-api.md) for a complete pool-backed service.
