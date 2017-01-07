#!/bin/bash

# Traditional Chinese to Simplified Chinese
echo "opencc: Traditional Chinese to Simplified Chinese..."
#time opencc -i wiki.zh.txt -o wiki.zh.chs.txt -c zht2zhs.ini
time opencc -i wiki.zh -o wiki.zh.chs -c t2s.json

# Cut words
echo "jieba: Cut words..."
time python -m jieba -d ' ' wiki.zh.chs > wiki.zh.chs.seg

# Change encode
echo "iconv: ascii to utf-8..."
time iconv -c -t UTF-8 < wiki.zh.chs.seg > wiki.zh.chs.seg.utf
