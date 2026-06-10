# Worker Location and Nearby Task Distance

ShelfSpot workers can update their current location whenever they open the application or move to a new working area. Nearby task discovery uses the worker's latest saved coordinates and the task coordinates.

## Coordinates

- Worker coordinates are stored on `workers.last_latitude` and `workers.last_longitude`.
- Task coordinates are stored on `tasks.latitude` and `tasks.longitude`.
- Latitude must be between `-90` and `90`.
- Longitude must be between `-180` and `180`.

## Default and expandable radius

The nearby tasks endpoint starts with a default radius of `5 km` when the worker does not send `radius_km`. The worker can expand the search circle by passing `radius_km`, up to `100 km`.

## Haversine formula

ShelfSpot uses the Haversine great-circle distance formula because task and worker locations are points on Earth rather than a flat plane.

Given:

- Worker latitude: `lat1`
- Worker longitude: `lon1`
- Task latitude: `lat2`
- Task longitude: `lon2`
- Earth radius: `R = 6371.0088 km`

Convert degree values to radians, then calculate:

```text
Δlat = lat2 - lat1
Δlon = lon2 - lon1

a = sin²(Δlat / 2) + cos(lat1) × cos(lat2) × sin²(Δlon / 2)

c = 2 × asin(min(1, √a))

distance = R × c
```

The `min(1, √a)` guard prevents floating-point rounding from producing invalid values slightly above `1`.

## Database performance strategy

Nearby task lookup uses two steps:

1. **Bounding box pre-filter**: calculate minimum and maximum latitude/longitude for the requested radius and use indexed range filters. This avoids running trigonometric calculations over all tasks.
2. **Haversine exact filter and sorting**: calculate exact distance for the much smaller candidate set, keep only tasks inside the requested radius, and sort nearest first.

This keeps the implementation readable while making the query scalable for large task volumes.

## Current availability rules

A task is visible in worker nearby search when it is:

- `active`
- not assigned to another worker
- inside the selected search radius
- inside any optional date filters sent by the worker
