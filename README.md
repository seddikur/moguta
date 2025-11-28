# moguta



# Project1
lftp -c "open -u user1,pass1 ftp.site1.com; mirror -c /remote-path/ ./project1/"

# Project2  
lftp -c "open -u user2,pass2 ftp.site2.com; mirror -c /remote-path/ ./project2/"


# В корне рабочей папки
cd /рабочая-папка

# Обновляем project1
lftp -c "open -u user1,pass1 ftp.site1.com; mirror -c /path/ ./project1/"

# Обновляем project2
lftp -c "open -u user2,pass2 ftp.site2.com; mirror -c /path/ ./project2/"



#!/bin/bash
cd /рабочая-папка

echo "Updating project1..."
lftp -c "open -u $USER1,$PASS1 $HOST1; mirror -c $PATH1 ./project1/"

echo "Updating project2..."  
lftp -c "open -u $USER2,$PASS2 $HOST2; mirror -c $PATH2 ./project2/"

echo "Updating project3..."
lftp -c "open -u $USER3,$PASS3 $HOST3; mirror -c $PATH3 ./project3/"

git add .
git commit -m "Auto-update all projects: $(date +%Y-%m-%d_%H-%M-%S)"
echo "All projects updated!"