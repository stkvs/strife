import subprocess
import os

def run_script(script_path):
    if os.path.exists(script_path):
        try:
            subprocess.run(['python3', script_path], check=True)
            print(f"Successfully ran {script_path}")
        except subprocess.CalledProcessError as e:
            print(f"Error running {script_path}: {e}")
    else:
        print(f"Script {script_path} does not exist.")

def main():
    base_dir = os.path.dirname(os.path.abspath(__file__))
    scripts = [
        os.path.join(base_dir, 'database-dependencies', 'updatedb.py'),
        os.path.join(base_dir, 'database-dependencies', 'makeadmintable.py')
    ]

    for script in scripts:
        run_script(script)

if __name__ == "__main__":
    main()