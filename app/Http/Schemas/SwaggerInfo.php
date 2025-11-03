<?php

namespace App\Http\Schemas;

/**
 * @OA\Info(
 *     title="Project Management API",
 *     version="1.0.0",
 *     description="API documentation for Agile Project Management System - PM → Project → Sprint → Task workflow",
 *     @OA\Contact(
 *         email="support@example.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your access token from login. Example: 1|abc123xyz..."
 * )
 *
 * @OA\Security(
 *     security={{"sanctum": {}}}
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication endpoints"
 * )
 * @OA\Tag(
 *     name="Projects",
 *     description="Project management endpoints"
 * )
 * @OA\Tag(
 *     name="Sprints",
 *     description="Sprint management endpoints"
 * )
 * @OA\Tag(
 *     name="Tasks",
 *     description="Task management endpoints"
 * )
 * @OA\Tag(
 *     name="SubTasks",
 *     description="SubTask management endpoints"
 * )
 * @OA\Tag(
 *     name="Notifications",
 *     description="Notification management endpoints"
 * )
 */
class SwaggerInfo
{
    // This class exists only to hold Swagger annotations
}


