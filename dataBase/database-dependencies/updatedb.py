import json
import mysql.connector
import os
def get_db_info():
    # Correct the path to the JSON file
    json_path = os.path.join(os.path.dirname(__file__), '..', 'dbinfo.json')

    try:
        with open(json_path, "r") as file:
            db_info = json.load(file)
            return db_info
    except FileNotFoundError:
        print(f"File not found: {json_path}")
        exit(1)
    except json.JSONDecodeError:
        print(f"Error decoding JSON from file: {json_path}")
        exit(1)

def connect_to_db():
    db_info = get_db_info()
    conn = mysql.connector.connect(
        host=db_info["host"], user=db_info["user"], password=db_info["password"], database="messaging_app"
    )
    return conn

def create_database():
    db_info = get_db_info()
    conn = mysql.connector.connect(
        host=db_info["host"], user=db_info["user"], password=db_info["password"]
    )
    cursor = conn.cursor()
    cursor.execute("CREATE DATABASE IF NOT EXISTS messaging_app")
    conn.commit()
    cursor.close()
    conn.close()

def create_tables():
    conn = connect_to_db()
    cursor = conn.cursor()

    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        """
    )

    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS group_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            file_path VARCHAR(255),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
        """
    )

    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS private_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            message TEXT NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            file_path VARCHAR(255),
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
        );
        """
    )

    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS admins (
            username VARCHAR(255) PRIMARY KEY,
            role ENUM('mod', 'admin') NOT NULL
        );
        """
    )

    conn.commit()
    cursor.close()
    conn.close()

if __name__ == "__main__":
    try:
        create_database()
        create_tables()
        print("Database and tables created successfully!")
    except FileNotFoundError as e:
        print(e)
