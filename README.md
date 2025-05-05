# 🚀 CLUB_MANAGEMENT <img src="https://i.ibb.co/Kp5R7LgF/logo.png" alt="Club Management Logo" width="40"/>

*Empowering Clubs, Connecting Communities, Inspiring Engagement*

[![Last Commit](https://img.shields.io/github/last-commit/Ziedkm/club_management?style=flat-square&logo=github&label=last%20commit)](https://github.com/Ziedkm/club_management/commits/main)
[![Language Count](https://img.shields.io/github/languages/count/Ziedkm/club_management?style=flat-square)](https://github.com/Ziedkm/club_management)
[![Top Language](https://img.shields.io/github/languages/top/Ziedkm/club_management?style=flat-square&color=blueviolet)](https://github.com/Ziedkm/club_management)
[![License](https://img.shields.io/github/license/Ziedkm/club_management?style=flat-square&color=lightgrey)](LICENSE) 

---

**Now available on Mobile!**

<p align="center" style="border-radius: 25px;">
  <img src="https://i.ibb.co/jv0dHbqw/app-ios-android.jpg" alt="Mobile App Preview" width="100%" />
</p>

**Built with the core technologies:**

![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=flat-square&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/javascript-%23323330.svg?style=flat-square&logo=javascript&logoColor=%23F7DF1E)
![CSS3](https://img.shields.io/badge/css3-%231572B6.svg?style=flat-square&logo=css3&logoColor=white)
![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=flat-square&logo=html5&logoColor=white)

---

## Table of Contents

*   [Overview](#overview)
    *   [Why Club Management?](#why-club-management)
    *   [Core Features](#core-features)
*   [Getting Started](#getting-started)
    *   [Prerequisites](#prerequisites)
    *   [Installation](#installation)
    *   [Configuration](#configuration)
    *   [Database Setup](#database-setup)
    *   [Running the Application](#running-the-application)
*   [Usage](#usage)
*   [Testing](#testing)
*   [Deployment](#deployment)
*   [Contributing](#contributing)
*   [License](#license)
*   [Contact](#contact)

---

## Overview

**Club Management** (ClubNest) is a comprehensive web application designed to streamline the management of university clubs and associated events. It enhances student engagement, simplifies administrative tasks, and fosters a connected campus community.

### Why Club Management?

This project aims to simplify the complexities of club creation, member management, event organization, and communication within a university setting, while ensuring secure user interactions and providing administrators with robust control.

### Core Features

*   👤 **User Authentication & Roles:** Secure login/registration with distinct roles (Student, Club Leader/President, Admin).
*   💬 **User Messaging:** Real-time (or near real-time) private messaging between users.
*   🏛️ **Admin Controls:** Secure management panel for approving/rejecting clubs & events, managing users (ban/delete/edit role), and overseeing the platform.
*   👥 **Club Management:** Streamlined creation (with admin approval for students), editing, and management of club details, members, and roles (President, Member).
*   📅 **Event Management:** Simplified creation (with admin approval for leaders), editing, and display of club events, including poster uploads and details like location, date/time (start/end).
*   👍 **Interactive Feed:** Twitter-style event feed allowing users to Like, Comment, mark Interest, and RSVP (Participate).
*   📱 **Mobile Ready:** Accessible design and planned mobile application support.
*   📊 **(Planned) Statistics Module:** Future insights into application performance and user engagement.

---

## Getting Started

Follow these instructions to get a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

Ensure you have the following installed on your system:

*   **PHP:** Version 8.0 or higher recommended (check `composer.json` if available)
*   **Web Server:** Apache or Nginx (XAMPP, MAMP, WAMP, Docker setup, etc.)
*   **Database:** MySQL or MariaDB
*   **Composer:** PHP dependency manager ([Download & Install Composer](https://getcomposer.org/))
*   **Git:** Version control system ([Download & Install Git](https://git-scm.com/))

### Installation

Set up the project locally:

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/Ziedkm/club_management.git
    ```
    *(Replace with your actual repository URL)*

2.  **Navigate to the project directory:**
    ```bash
    cd club_management
    ```

3.  **Install PHP dependencies:**
    ```bash
    composer install
    ```
    *(If you add dependencies later, run this command)*

### Configuration

Configure your environment settings:

1.  **Environment File:** If an `.env.example` file exists, copy it to `.env`:
    ```bash
    cp .env.example .env
    ```
    Then, edit the `.env` file with your specific settings (database credentials, app URL, etc.).

2.  **Configuration File:** If there's no `.env` system, locate the main configuration file (e.g., `config/database.php` or a general `config/config.php`).
3.  **Update Settings:** Edit the configuration file(s) with:
    *   Your local database connection details (host, database name, username, password).
    *   The correct base URL for the application if needed.
    *   Paths for upload directories (ensure they match your setup).

### Database Setup

Set up the application database:

1.  **Create Database:** Using a tool like phpMyAdmin, MySQL Workbench, or the command line, create a new MySQL database (e.g., `clubnest`).
2.  **Import Schema & Data:** Import the provided `.sql` file (e.g., `database.sql` or the SQL commands you saved) into your newly created database. This will create the necessary tables (`users`, `clubs`, `events`, `messages`, etc.).

### Running the Application

1.  **Web Server:** Ensure your web server (Apache/Nginx via XAMPP, MAMP, etc.) is running.
2.  **Document Root:** Configure your web server's document root to point to the main project directory (e.g., `C:/xampp/htdocs/club_management`) or a specific `public` subdirectory if your project uses one.
3.  **Access:** Open your web browser and navigate to the URL configured for your local development environment (e.g., `http://localhost/club_management/` or `http://clubnest.test/` if using virtual hosts).

---

## Usage

Once installed and running:

1.  **Register/Login:** Create a student account or use predefined admin/leader accounts (check database setup).
2.  **Explore:** Navigate using the sidebar/navbar.
3.  **Students:** Browse clubs/events, join clubs, register for events, send messages. Propose a new club (requires admin approval).
4.  **Club Leaders:** Manage their assigned club(s), create events (requires admin approval), manage members (if implemented), send notifications.
5.  **Admins:** Access the Admin Panel to approve/reject clubs/events, manage users (ban, delete, edit role), manage all clubs/events.

---

## Testing

This project uses **PHPUnit** (or specify your testing framework) for automated testing.

1.  **Configure Test Database:** You might need to set up a separate database for testing (check testing configuration files).
2.  **Run Tests:** Execute the test suite using Composer:
    ```bash
    vendor/bin/phpunit
    ```

---

## Deployment

To deploy this application to a live server (like the free hosting you set up):

1.  **Upload Files:** Transfer all project files (excluding development files like `.git`, `.env` if applicable) to your host's web root directory (e.g., `htdocs`) via FTP (FileZilla) or the host's file manager.
2.  **Export/Import Database:** Export your local database and import it into the database created on your hosting provider (using their phpMyAdmin).
3.  **Configure Online:** Update the `config/database.php` file (or `.env` if supported) on the live server with the hosting provider's database credentials (host, DB name, username, password).
4.  **Set Permissions:** Ensure directories that require writing (like `uploads/event_posters/`, session storage, cache if any) have the correct write permissions set by the web server (often 755 or sometimes 777 - check host documentation, use 777 cautiously).
5.  **Check PHP Version:** Ensure the hosting provider supports the required PHP version.

---

## Contributing

Contributions are welcome! Please read the `CONTRIBUTING.md` file (you should create this) for details on the process for submitting pull requests. Adhere to the project's code of conduct.

---

## License

This project is licensed under the **MIT License** - see the `LICENSE` file (you should create this) for details.

---

## Contact

*   **Project Lead:** - [ziedkmantar@gmail.com](mailto:ziedkmantar@gmail.com)
*   **Project Link:** [https://github.com/Ziedkm/club_management](https://github.com/Ziedkm/club_management)

---
