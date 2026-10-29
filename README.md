# 3-Tier Movie Booking Application on AWS

## Overview

This project demonstrates a three-tier web application architecture using Amazon Web Services (AWS). The application is designed to separate the presentation layer, application logic, and database layer.

## Architecture

1. **Web Tier:** Amazon EC2 running Nginx to serve the frontend.
2. **Application Tier:** A separate EC2 instance running the backend application logic.
3. **Database Tier:** Amazon RDS running MySQL to store application data.

## Technologies Used

- AWS EC2
- Amazon RDS
- MySQL
- Nginx
- Linux
- HTML
- CSS
- JavaScript

## Features

- Movie booking website frontend
- Three-tier deployment architecture
- Separate web, application, and database tiers
- Cloud-hosted infrastructure on AWS

## Project Structure

The repository contains the frontend, backend, database scripts, and deployment documentation as applicable to the implementation.

## Deployment

Deployment instructions, AWS configuration, and database setup will be documented here as the project is finalized.

## Security

- Do not commit passwords, AWS access keys, private SSH keys, or database credentials.
- Restrict database access to the application tier.
- Configure security groups using the principle of least privilege.

## Author

Project developed as a hands-on AWS cloud learning project.
