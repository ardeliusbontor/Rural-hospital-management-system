# 🏥 Rural Health Camp Management System
**BCA Major Project | Group BCA_31 | GGI**

---

## 📋 How to Setup (Step by Step)

### Step 1 — Install XAMPP
- Download XAMPP from: https://www.apachefriends.org/
- Install it and start **Apache** and **MySQL** from XAMPP Control Panel

### Step 2 — Copy Project Files
- Copy the `health_camp` folder to: `C:/xampp/htdocs/health_camp`

### Step 3 — Setup Database
- Open your browser and go to: `http://localhost/phpmyadmin`
- Click **"New"** on the left side
- Create a database named: `health_camp_db`
- Click the database, then click **"Import"** tab
- Click "Choose File" and select `database.sql` from the project folder
- Click **"Go"** to import

### Step 4 — Run the Project
- Open browser and go to: `http://localhost/health_camp/login.php`

### Step 5 — Login
- **Username:** `admin`
- **Password:** `admin123`

---

## 📁 Project Files Explained

```
health_camp/
│
├── login.php              ← Login page (start here)
├── index.php              ← Dashboard after login
├── logout.php             ← Logout (clears session)
├── database.sql           ← SQL file to setup database
│
├── css/
│   └── style.css          ← All styling/colors here
│
├── includes/
│   ├── db.php             ← Database connection
│   └── sidebar.php        ← Left side menu
│
└── pages/
    ├── patients.php        ← Register & view patients
    ├── doctors.php         ← Manage doctors
    ├── medicines.php       ← Medicine inventory
    └── reports.php         ← Reports & statistics
```

---

## 💻 Technologies Used

| Part       | Technology                    |
|------------|-------------------------------|
| Frontend   | HTML5, CSS3, Bootstrap 5      |
| JavaScript | Vanilla JS (basic)            |
| Backend    | PHP (Core PHP)                |
| Database   | MySQL (via phpMyAdmin)        |
| Server     | Apache (XAMPP)                |
| Icons      | Font Awesome 6                |

---

## ✨ Features

- ✅ Login / Logout system with sessions
- ✅ Dashboard with live stats (total patients, doctors, etc.)
- ✅ Add / View / Delete Patients
- ✅ Search patients by name or village
- ✅ Add / View / Delete Doctors
- ✅ Medicine Inventory with stock management
- ✅ Reports with date filter + print option
- ✅ Patients count by village, disease, doctor
- ✅ Mobile responsive design

---

## 👥 Group Members

| Name            | Roll Number |
|-----------------|-------------|
| Saurab Kumar    | 2338819     |
| Anjali Kumari   | 2311155     |
| Komal           | 2311225     |
| Ishu            | 2311205     |
| Ardelius Bontor | 2311160     |

**Project Guide:** Navjot Kaur

---

## 📝 Notes for Beginners

- All PHP files start with `session_start()` — this keeps user logged in
- `mysqli_query()` is used to talk to the database
- `$_POST` gets form data, `$_GET` gets URL data
- `htmlspecialchars()` is used to prevent basic security issues
- `intval()` converts text to a number safely
