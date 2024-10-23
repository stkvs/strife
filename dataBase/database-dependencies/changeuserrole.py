import mysql.connector

def change_user_role():
    # Database connection
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="messaging_app"
    )
    cursor = conn.cursor()

    # Show all users
    cursor.execute("SELECT username FROM users")
    users = cursor.fetchall()
    print("Current users:")
    for user in users:
        print(user[0])

    print("Roles available: mod, admin")
    # Get input from user
    username = input("Enter the username: ")
    role = input("Enter the role: ")

    # Check if the user exists in the users table
    cursor.execute("SELECT * FROM users WHERE username = %s", (username,))
    user = cursor.fetchone()

    if user:
        # Insert into admins table
        cursor.execute("INSERT INTO admins (username, role) VALUES (%s, %s)", (username, role))
        conn.commit()
        print("User role updated successfully.")
    else:
        print("User does not exist in the users table.")

    # Close the connection
    cursor.close()
    conn.close()

if __name__ == "__main__":
    change_user_role()