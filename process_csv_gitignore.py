#!/usr/bin/env python3
"""
Script to read CSV file and add entries marked as 'Ignore' or with blank GitHub Action to .gitignore
"""

import csv
import os
import sys

def process_csv_to_gitignore(csv_filepath, gitignore_filepath='.gitignore', replace=True):
    """
    Read CSV file and create/replace .gitignore with entries marked as 'Ignore' or blank
    
    Args:
        csv_filepath: Path to the CSV file
        gitignore_filepath: Path to .gitignore file (default: .gitignore)
        replace: If True, replace existing .gitignore; if False, append to it
    """
    
    # Check if CSV file exists
    if not os.path.exists(csv_filepath):
        print(f"Error: CSV file '{csv_filepath}' not found!")
        return False
    
    # Read CSV and collect files to ignore
    files_to_ignore = []
    
    try:
        with open(csv_filepath, 'r', encoding='utf-8') as csvfile:
            # Try to detect delimiter
            sample = csvfile.read(1024)
            csvfile.seek(0)
            delimiter = ','
            if '\t' in sample:
                delimiter = '\t'
            
            reader = csv.DictReader(csvfile, delimiter=delimiter)
            
            # Check if required columns exist
            if 'GitHub Action' not in reader.fieldnames or 'Full Path and Filename' not in reader.fieldnames:
                print("Error: Required columns 'GitHub Action' and 'Full Path and Filename' not found!")
                print(f"Available columns: {reader.fieldnames}")
                return False
            
            # Process each row
            for row in reader:
                # Include both 'Ignore' and blank/empty values
                github_action = row['GitHub Action'].strip() if row['GitHub Action'] else ''
                if github_action == 'Ignore' or github_action == '':
                    filepath = row['Full Path and Filename'].strip()
                    if filepath:
                        files_to_ignore.append(filepath)
    
    except Exception as e:
        print(f"Error reading CSV file: {e}")
        return False
    
    # If no files to ignore, still create empty .gitignore
    if not files_to_ignore:
        print("No files found to ignore in CSV")
        # Still create an empty .gitignore if replace is True
        if replace and os.path.exists(gitignore_filepath):
            os.remove(gitignore_filepath)
            print(f"Removed existing {gitignore_filepath}")
        return True
    
    # Remove duplicates and sort
    files_to_ignore = sorted(list(set(files_to_ignore)))
    
    # Delete existing .gitignore if replace is True
    if replace and os.path.exists(gitignore_filepath):
        os.remove(gitignore_filepath)
        print(f"Removed existing {gitignore_filepath}")
    
    # Write to .gitignore
    try:
        mode = 'w' if replace else 'a'
        with open(gitignore_filepath, mode) as f:
            # Add header
            f.write("# Auto-generated from CSV analysis\n")
            f.write("# Files and directories that should not be tracked by Git\n\n")
            
            # Add common Git ignores first
            f.write("# Git files\n")
            f.write(".git/\n\n")
            
            f.write("# Files from CSV analysis\n")
            for filepath in files_to_ignore:
                f.write(f"{filepath}\n")
        
        print(f"Successfully created {gitignore_filepath} with {len(files_to_ignore)} entries")
        print("\nFirst 10 entries:")
        for filepath in files_to_ignore[:10]:
            print(f"  - {filepath}")
        if len(files_to_ignore) > 10:
            print(f"  ... and {len(files_to_ignore) - 10} more")
            
    except Exception as e:
        print(f"Error writing to .gitignore: {e}")
        return False
    
    return True

def main():
    # Default CSV filename
    csv_file = "Analyzed - Diff Analysis Report - Data.csv"
    
    # Check command line arguments
    if len(sys.argv) > 1:
        csv_file = sys.argv[1]
    
    # Run the processor
    print(f"Processing CSV file: {csv_file}")
    success = process_csv_to_gitignore(csv_file)
    
    if success:
        print("\nDone! Don't forget to:")
        print("1. Review the updated .gitignore file")
        print("2. Run 'git rm -r --cached .' to remove already tracked files")
        print("3. Run 'git add .' to re-add files with new .gitignore rules")
        print("4. Commit your changes")
    else:
        sys.exit(1)

if __name__ == "__main__":
    main()