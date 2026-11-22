---
paths:
  - "resources/js/components/**"
  - "resources/js/hooks/**"
  - "resources/js/lib/**"
  - "resources/js/pages/**"
  - "routes/**"
---

# WEB>HTTP: Frontend → backend (requests)

From: PAGE (Pages), COMP (Components), LIB (Frontend helpers)  
To: ROUTE (Routes)

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Carries

- **WEB>HTTP-1** The user's input and actions. `note`

## Shape

- **WEB>HTTP-2** Sent through Inertia (router, useForm, the Form component); URLs and request functions come from Wayfinder. `reviewer`
  - Check: Look at: requests and links in pages, components, lib and hooks. Break: a request without router, useForm or <Form>; a URL not from Wayfinder ('/tasks', `/tasks/${id}`). Not: <Form {...store.form()}>, router.post(update(id)), href={index()}, router.reload({ only }); links to outside sites. Cite: the line.

## Before it passes

- **WEB>HTTP-3** A change to many records is sent as one request. `reviewer`
  - Check: Look at: loops and Promise.all around requests. Break: one request per record for one change (ids.forEach(router.post), Promise.all(ids.map(...))). Not: one request carrying the ids or useRowSelection's all-results flag; separate clicks. Cite: the loop line.

## Must not

- **WEB>HTTP-4** Call fetch, axios or XMLHttpRequest, also not for outside sites; the app's own URLs come only from Wayfinder, and only links to outside sites are written by hand. `reviewer`
  - Check: Look at: resources/js except the generated actions/, routes/ and wayfinder/. Break: fetch(, axios, XMLHttpRequest, sendBeacon, EventSource; an app URL written by hand. Not: Wayfinder imports; https:// links to outside sites in href. Cite: the line.
