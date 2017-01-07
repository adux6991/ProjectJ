'''
import subprocess, random, sys

postid = sys.argv[1]
string = sys.argv[2]

with open("/Library/WebServer/Documents/api/log.html", "a") as fout:
    fout.write(string + '\n')

vec = []
for i in range(200):
    vec.append(str(random.random()))

subprocess.call(["php", "/Library/WebServer/Documents/api/recommend.php", postid, ' '.join(vec)])


'''
from gensim.corpora import Dictionary
from gensim.models import LdaModel
import jieba, logging, math, codecs, sys, subprocess

postid = sys.argv[1]
doc = sys.argv[2]
doc = doc.strip()
doc = list(jieba.cut(doc))

print "1"

stopwords = codecs.open('/Library/WebServer/Documents/api/recommend/stopwords.txt', 'r', encoding = 'utf8').readlines()
stopwords = [w.strip() for w in stopwords]
doc = [w for w in doc if w not in stopwords]
del stopwords

print "2"

dictionary = Dictionary.load_from_text('/Library/WebServer/Documents/api/recommend/wiki.dictionary.bz2')
doc_bow = dictionary.doc2bow(doc)
del dictionary
del doc

print "3"

lda = LdaModel.load('/Library/WebServer/Documents/api/recommend/lda/lda.model')
result = lda.inference([doc_bow])
del lda
del doc_bow

print "4"

vec = result[0][0]
s = []
for i in range(200):
    s.append(str(vec[i]))

#subprocess.call("php /path/to/test.php")

subprocess.call(["php", "/Library/WebServer/Documents/api/recommend.php", postid, ' '.join(s)])
