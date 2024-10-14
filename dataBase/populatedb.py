import mysql.connector
import random
import string

def connect_to_db():
    conn = mysql.connector.connect(
        host="localhost", user="root", password="", database="messaging_app"
    )
    return conn

def random_string(length=20):
    letters = string.ascii_letters + string.digits
    return "".join(random.choice(letters) for i in range(length))

def create_sample_users(cursor, num_users=10):
    for i in range(num_users):
        username = f"user{i + 1}"
        password = random_string(10)
        cursor.execute(
            "INSERT INTO users (username, password) VALUES (%s, %s)",
            (username, password),
        )
    print(f"Created {num_users} sample users.")

def populate_database():
    conn = connect_to_db()
    cursor = conn.cursor()

    create_sample_users(cursor)

    cursor.execute("SELECT COUNT(*) FROM users")
    user_count = cursor.fetchone()[0]
    print(f"Total users in database: {user_count}")

    group_ids = []
    for i in range(1, 11):
        group_name = f"Group {i}"
        cursor.execute(
            "INSERT INTO groups (group_name, created_by) VALUES (%s, %s)",
            (group_name, 1),
        )
        group_ids.append(cursor.lastrowid)

    print(f"Created groups with IDs: {group_ids}")

    for _ in range(30):
        user_id = random.randint(1, user_count)
        group_id = random.choice(group_ids)
        message = f"Message from {user_id} in group {group_id}: {random_string()}"
        try:
            cursor.execute(
                "INSERT INTO group_messages (user_id, group_id, message) VALUES (%s, %s, %s)",
                (user_id, group_id, message),
            )
        except mysql.connector.Error as err:
            print(f"Error inserting group message: {err}")

    for _ in range(30):
        sender_id = random.randint(1, user_count)
        receiver_id = random.randint(1, user_count)
        while sender_id == receiver_id:
            receiver_id = random.randint(1, user_count)
        message = f"Private message from {sender_id} to {receiver_id}: {random_string()}"
        try:
            cursor.execute(
                "INSERT INTO private_messages (sender_id, receiver_id, message) VALUES (%s, %s, %s)",
                (sender_id, receiver_id, message),
            )
        except mysql.connector.Error as err:
            print(f"Error inserting private message: {err}")

    conn.commit()
    cursor.close()
    conn.close()
    print("Database populated with sample data!")

if __name__ == "__main__":
    populate_database()
