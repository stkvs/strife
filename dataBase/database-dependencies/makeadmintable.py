import mysql.connector
from mysql.connector import errorcode
import json
import os

# Correct the path to the JSON file
json_path = os.path.join(os.path.dirname(__file__), '..', 'dbinfo.json')

try:
    with open(json_path, "r") as file:
        db_info = json.load(file)
except FileNotFoundError:
    print(f"File not found: {json_path}")
    exit(1)
except json.JSONDecodeError:
    print(f"Error decoding JSON from file: {json_path}")
    exit(1)

# Ensure the required keys are in the JSON
required_keys = ["host", "user", "password"]
for key in required_keys:
    if key not in db_info:
        print(f"Missing required key in JSON: {key}")
        exit(1)

config = {
    "host": db_info["host"],
    "user": db_info["user"],
    "password": db_info["password"],
    "database": "messaging_app"
}

conn = None

try:
    # Connect to the database
    conn = mysql.connector.connect(**config)
    cursor = conn.cursor()

    # Create the admins table
    cursor.execute('''
    CREATE TABLE IF NOT EXISTS admins (
        username VARCHAR(255) PRIMARY KEY,
        role ENUM('mod', 'admin') NOT NULL
    )
    ''')

    # Commit the changes
    conn.commit()
    print("Admins table created successfully.")

except mysql.connector.Error as err:
    if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
        print("Something is wrong with your user name or password")
    elif err.errno == errorcode.ER_BAD_DB_ERROR:
        print("Database does not exist")
    else:
        print(err)
finally:
    # Close the connection
    if conn is not None and conn.is_connected():
        cursor.close()
        conn.close()