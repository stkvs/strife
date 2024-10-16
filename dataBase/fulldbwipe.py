import mysql.connector


def connect_to_db():
    conn = mysql.connector.connect(
        host="localhost", user="root", password="", database="messaging_app"
    )
    return conn


def flush_database():
    conn = connect_to_db()
    cursor = conn.cursor()

    try:
        cursor.execute("SET FOREIGN_KEY_CHECKS = 0;")

        tables = [
            "group_messages",
            "private_messages",
            "users",
            "admins",
        ]

        for table in tables:
            cursor.execute(f"TRUNCATE TABLE {table};")
            print(f"Flushed table: {table}")

    except mysql.connector.Error as err:
        print(f"Error flushing tables: {err}")

    finally:
        cursor.execute("SET FOREIGN_KEY_CHECKS = 1;")

    conn.commit()
    cursor.close()
    conn.close()
    print("All tables flushed successfully!")


if __name__ == "__main__":
    flush_database()
