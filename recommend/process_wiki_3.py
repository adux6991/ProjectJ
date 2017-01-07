import codecs, logging, sys
from gensim.models import LdaModel
from gensim.corpora import Dictionary

if len(sys.argv) < 3:
    print 'Usage: \n python process_wiki_3.py wiki.zh.chs.seg.utf wiki.zh.chs.seg.utf.stop'
    sys.exit(1)

inp, outp = sys.argv[1:3]

logging.basicConfig(format = '%(asctime)s: %(levelname)s: %(message)s', level = logging.INFO)
logging.info('Removing stop words...')
stopwords = codecs.open('stopwords.txt', 'r', encoding='utf8').readlines()
stopwords = [ w.strip() for w in stopwords ]
fin = codecs.open(inp, 'r', encoding='utf8')
fout = codecs.open(outp, 'w', encoding='utf8')

for line in fin:
    line = line.split()
    line = ' '.join([ w for w in line if w not in stopwords ])
    fout.write(line + '\n')

fin.close()
fout.close()
