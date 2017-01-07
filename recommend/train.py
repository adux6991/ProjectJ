import codecs, logging, sys
from gensim.models import LdaModel, TfidfModel
from gensim.corpora import Dictionary

if len(sys.argv) < 3:
    print 'Usage: \n python train.py wiki.zh.chs.seg.utf.stop lda.model'
    sys.exit(1)

inp, outp = sys.argv[1:3]

logging.basicConfig(format = '%(asctime)s: %(levelname)s: %(message)s', level = logging.INFO)
logging.info('Loading training set...')
fp = codecs.open(inp, 'r', encoding='utf8')
train = []
for line in fp:
    train.append(line.split())
fp.close()

logging.info('Preparing corpus...')
dictionary = Dictionary(train)
dictionary.save_as_text('wiki.dictionary.bz2')
corpus = [ dictionary.doc2bow(text) for text in train ]
tfidf = TfidfModel(corpus)
corpus_tfidf = tfidf[corpus]

del train, tfidf

logging.info('Training...')
lda = LdaModel(corpus_tfidf, id2word=dictionary, num_topics=200)
#lda = LdaMulticore(corpus=corpus, id2word=dictionary, num_topics=100, workers=2)

logging.info('Saving LDA model...')
lda.save(outp)
