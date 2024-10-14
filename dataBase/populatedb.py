import mysql.connector
import random
import string


# Function to connect to the database
def connect_to_db():
    conn = mysql.connector.connect(
        host="localhost", user="root", password="", database="messaging_app"
    )
    return conn


# Function to generate a random string
def random_string(length=20):
    letters = string.ascii_letters + string.digits
    return "".join(random.choice(letters) for i in range(length))


# Function to create sample users
def create_sample_users(cursor, num_users=10):
    for i in range(num_users):
        username = f"user{i + 1}"
        password = random_string(10)  # Random password
        email = f"{username}@example.com"
        cursor.execute(
            "INSERT INTO users (username, password, email) VALUES (%s, %s, %s)",
            (username, password, email),
        )
    print(f"Created {num_users} sample users.")


# Function to populate the database with sample data
def populate_database():
    conn = connect_to_db()
    cursor = conn.cursor()

    # Create sample users
    create_sample_users(cursor)

    # Check the number of users created
    cursor.execute("SELECT COUNT(*) FROM users")
    user_count = cursor.fetchone()[0]
    print(f"Total users in database: {user_count}")

    # Insert 10 groups (assuming the first user is the creator)
    group_ids = []
    for i in range(1, 11):
        group_name = f"Group {i}"
        cursor.execute(
            "INSERT INTO groups (group_name, created_by) VALUES (%s, %s)",
            (group_name, 1),  # Assuming user with ID 1 creates all groups
        )
        group_ids.append(cursor.lastrowid)  # Store the last inserted group id

    # Print created group IDs for debugging
    print(f"Created groups with IDs: {group_ids}")

    # Insert random group messages
    for _ in range(30):  # 30 random messages
        user_id = random.randint(1, user_count)  # Random user ID from existing users
        group_id = random.choice(
            group_ids
        )  # Choose a valid group id from created groups
        message = f"Message from {user_id} in group {group_id}: {random_string()}"
        try:
            cursor.execute(
                "INSERT INTO group_messages (user_id, group_id, message) VALUES (%s, %s, %s)",
                (user_id, group_id, message),
            )
        except mysql.connector.Error as err:
            print(f"Error inserting group message: {err}")

    # Insert random private messages
    for _ in range(30):  # 30 random private messages
        sender_id = random.randint(1, user_count)
        receiver_id = random.randint(1, user_count)
        while sender_id == receiver_id:  # Ensure sender and receiver are not the same
            receiver_id = random.randint(1, user_count)
        message = (
            f"Private message from {sender_id} to {receiver_id}: {random_string()}"
        )
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
